#!/usr/bin/env python3
"""Build and verify a deterministic, tracked-files-only deployment ZIP."""

from __future__ import annotations

import argparse
import hashlib
import json
import pathlib
import re
import subprocess
import zipfile


SECRET_PATTERNS = {
    "private key": re.compile(rb"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----"),
    "OpenAI key": re.compile(rb"\bsk-[A-Za-z0-9_-]{20,}\b"),
    "Azure account key": re.compile(rb"\bAccountKey=[^;\s]{12,}", re.IGNORECASE),
    "shared access signature": re.compile(
        rb"\bSharedAccessSignature=[^;\s]{12,}", re.IGNORECASE
    ),
}


def git_lines(*args: str) -> list[str]:
    output = subprocess.check_output(["git", *args], text=True)
    return [line for line in output.splitlines() if line]


def read_exclusions(path: pathlib.Path) -> list[str]:
    return [
        line.strip()
        for line in path.read_text(encoding="utf-8").splitlines()
        if line.strip() and not line.lstrip().startswith("#")
    ]


def matches_exclusion(name: str, exclusions: list[str]) -> bool:
    for rule in exclusions:
        if rule.endswith("/") and name.startswith(rule):
            return True
        if rule.endswith("*") and name.startswith(rule[:-1]):
            return True
        if rule.startswith("*") and name.endswith(rule[1:]):
            return True
        if name == rule:
            return True
    return False


def git_blob(commit: str, name: str) -> bytes:
    return subprocess.check_output(["git", "show", f"{commit}:{name}"])


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--commit", required=True)
    parser.add_argument("--output", required=True, type=pathlib.Path)
    parser.add_argument("--exclusions", required=True, type=pathlib.Path)
    parser.add_argument("--inventory", required=True, type=pathlib.Path)
    args = parser.parse_args()

    commit = subprocess.check_output(
        ["git", "rev-parse", f"{args.commit}^{{commit}}"], text=True
    ).strip()
    tracked = git_lines("ls-tree", "-r", "--name-only", commit)
    exclusions = read_exclusions(args.exclusions)
    excluded = [name for name in tracked if matches_exclusion(name, exclusions)]
    included = [name for name in tracked if name not in set(excluded)]

    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.inventory.parent.mkdir(parents=True, exist_ok=True)

    with zipfile.ZipFile(
        args.output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9
    ) as package:
        for name in included:
            if "\\" in name or name.startswith("/") or name.startswith("../"):
                raise SystemExit(f"Unsafe ZIP entry: {name}")
            payload = git_blob(commit, name)
            for label, pattern in SECRET_PATTERNS.items():
                if pattern.search(payload):
                    raise SystemExit(f"Credential scan failed ({label}): {name}")
            entry = zipfile.ZipInfo(name, date_time=(1980, 1, 1, 0, 0, 0))
            entry.create_system = 3
            entry.external_attr = 0o100644 << 16
            package.writestr(entry, payload, compress_type=zipfile.ZIP_DEFLATED)

    with zipfile.ZipFile(args.output) as package:
        entries = [entry.filename for entry in package.infolist() if not entry.is_dir()]
        if entries != included:
            raise SystemExit("ZIP inventory differs from expected deployment inventory")
        if any("\\" in entry.filename for entry in package.infolist()):
            raise SystemExit("ZIP contains a Windows path separator")
        if any(matches_exclusion(name, exclusions) for name in entries):
            raise SystemExit("ZIP contains a documented excluded path")

    digest = hashlib.sha256(args.output.read_bytes()).hexdigest()
    inventory = {
        "commit": commit,
        "artifact": args.output.name,
        "sha256": digest,
        "size_bytes": args.output.stat().st_size,
        "tracked_file_count": len(tracked),
        "included_file_count": len(included),
        "excluded_file_count": len(excluded),
        "exclusion_rules": exclusions,
        "excluded_tracked_files": excluded,
        "included_files": included,
    }
    args.inventory.write_text(
        json.dumps(inventory, indent=2, sort_keys=True) + "\n", encoding="utf-8"
    )
    print(json.dumps({key: inventory[key] for key in (
        "commit", "artifact", "sha256", "size_bytes", "tracked_file_count",
        "included_file_count", "excluded_file_count"
    )}, sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
