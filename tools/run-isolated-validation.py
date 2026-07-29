#!/usr/bin/env python3
"""Run PHP validation suites in a disposable copy without touching live data."""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import pathlib
import shutil
import subprocess
import sys
import tempfile
from typing import Iterable


MUTABLE_DIRECTORIES = ("portal-data", "portal-uploads", "submissions")
DEPLOYMENT_RESIDUE = (
    "hostingstart.html",
    "rc1-apps-temp.json",
    "rc1-final-report-temp.txt.gz",
    "rc1-prod-fs-audit-temp.txt.gz",
    "rc1-prod-fs-audit2-temp.txt.gz",
    "rc1-production-audit-temp.json",
    "rc1-production-audit-temp.json.gz",
    "rc1-production-audit2-temp.json.gz",
)
PRODUCTION_LIKE_ROOTS = (
    pathlib.Path("/home/site/wwwroot").resolve(),
    pathlib.Path("/home/site/wwwroot/portal-data").resolve(),
    pathlib.Path("/home/site/wwwroot/portal-uploads").resolve(),
    pathlib.Path("/home/site/wwwroot/submissions").resolve(),
)


def path_is_within(path: pathlib.Path, parent: pathlib.Path) -> bool:
    try:
        path.relative_to(parent)
        return True
    except ValueError:
        return False


def reject_unsafe_temp_parent(path: pathlib.Path) -> pathlib.Path:
    resolved = path.resolve()
    if any(
        resolved == root or path_is_within(resolved, root)
        for root in PRODUCTION_LIKE_ROOTS
    ):
        raise ValueError(f"Refusing production-like temporary root: {resolved}")
    return resolved


def file_inventory(root: pathlib.Path) -> dict[str, dict[str, object]]:
    inventory: dict[str, dict[str, object]] = {}
    if not root.exists():
        return inventory
    for path in sorted(item for item in root.rglob("*") if item.is_file()):
        relative = path.relative_to(root).as_posix()
        inventory[relative] = {
            "size": path.stat().st_size,
            "sha256": hashlib.sha256(path.read_bytes()).hexdigest(),
        }
    return inventory


def mutable_inventory(source: pathlib.Path) -> dict[str, dict[str, object]]:
    return {
        directory: file_inventory(source / directory)
        for directory in MUTABLE_DIRECTORIES
    }


def copy_application(source: pathlib.Path, target: pathlib.Path) -> None:
    excluded = set(MUTABLE_DIRECTORIES + DEPLOYMENT_RESIDUE)

    def ignore(_directory: str, names: list[str]) -> set[str]:
        return {name for name in names if name in excluded}

    shutil.copytree(source, target, ignore=ignore)
    for directory in MUTABLE_DIRECTORIES:
        mutable = target / directory
        mutable.mkdir(mode=0o700)
        (mutable / ".htaccess").write_text("Deny from all\n", encoding="utf-8")


def write_log(path: pathlib.Path, command: list[str], result: subprocess.CompletedProcess[str]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(
        json.dumps(
            {
                "command": command,
                "exit_code": result.returncode,
                "stdout": result.stdout,
                "stderr": result.stderr,
            },
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )


def run_suites(
    isolated_root: pathlib.Path,
    php: str,
    suites: Iterable[str],
    log_directory: pathlib.Path,
) -> int:
    environment = os.environ.copy()
    environment.update(
        {
            "APP_ENV": "test",
            "YUVA_TEST_ISOLATED_ROOT": str(isolated_root),
            "SQL_APPROVAL_ENABLED": "false",
            "AI_MENTOR_COACH_ME_ENABLED": "false",
            "AI_MENTOR_MEDIA_ANALYSIS_ENABLED": "false",
            "AI_MENTOR_WEEKLY_REPORTS_ENABLED": "false",
            "AI_MENTOR_GUIDED_MENTOR_ENABLED": "false",
            "AI_MENTOR_PREMIUM_ENTITLEMENT_ENABLED": "false",
        }
    )
    for index, suite in enumerate(suites, start=1):
        command = [php, suite]
        result = subprocess.run(
            command,
            cwd=isolated_root,
            env=environment,
            text=True,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            check=False,
        )
        write_log(log_directory / f"{index:02d}-{pathlib.Path(suite).name}.json", command, result)
        sys.stdout.write(result.stdout)
        sys.stderr.write(result.stderr)
        if result.returncode != 0:
            return result.returncode
    return 0


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--source", required=True, type=pathlib.Path)
    parser.add_argument("--php", default="php")
    parser.add_argument("--suite", action="append", required=True)
    parser.add_argument("--log-directory", required=True, type=pathlib.Path)
    parser.add_argument("--temp-parent", type=pathlib.Path)
    args = parser.parse_args()

    source = args.source.resolve()
    if not source.is_dir():
        raise SystemExit(f"Source directory does not exist: {source}")
    temp_parent = (
        reject_unsafe_temp_parent(args.temp_parent)
        if args.temp_parent is not None
        else None
    )
    before = mutable_inventory(source)
    temporary = pathlib.Path(
        tempfile.mkdtemp(prefix="yuva-isolated-validation-", dir=temp_parent)
    )
    isolated_root = temporary / "application"
    exit_code = 1
    try:
        copy_application(source, isolated_root)
        if os.path.commonpath([source, isolated_root]) == str(source):
            raise RuntimeError("Isolated validation root is inside the source tree")
        for directory in MUTABLE_DIRECTORIES:
            mutable = (isolated_root / directory).resolve()
            if path_is_within(mutable, source):
                raise RuntimeError("Mutable test root was not redirected")
        exit_code = run_suites(
            isolated_root,
            args.php,
            args.suite,
            args.log_directory.resolve(),
        )
        after = mutable_inventory(source)
        if before != after:
            raise RuntimeError("Live mutable-path inventory changed during validation")
        return exit_code
    finally:
        shutil.rmtree(temporary, ignore_errors=False)
        if temporary.exists():
            raise RuntimeError(f"Temporary validation directory remains: {temporary}")


if __name__ == "__main__":
    raise SystemExit(main())
