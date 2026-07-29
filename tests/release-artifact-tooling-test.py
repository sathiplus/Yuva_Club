#!/usr/bin/env python3
"""Release packaging regression test, including mutable-overlay preservation."""

from __future__ import annotations

import hashlib
import json
import pathlib
import subprocess
import sys
import tempfile
import zipfile


ROOT = pathlib.Path(__file__).resolve().parents[1]
BUILDER = ROOT / "tools" / "build-release-artifact.py"
EXCLUSIONS = ROOT / "tools" / "release-artifact-exclusions.txt"
MUTABLE_PREFIXES = ("portal-data/", "portal-uploads/", "submissions/")


def digest(path: pathlib.Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


with tempfile.TemporaryDirectory() as temp_name:
    temp = pathlib.Path(temp_name)
    artifact = temp / "release.zip"
    inventory_path = temp / "inventory.json"
    subprocess.run(
        [
            sys.executable,
            str(BUILDER),
            "--commit",
            "HEAD",
            "--output",
            str(artifact),
            "--exclusions",
            str(EXCLUSIONS),
            "--inventory",
            str(inventory_path),
        ],
        cwd=ROOT,
        check=True,
    )

    inventory = json.loads(inventory_path.read_text(encoding="utf-8"))
    with zipfile.ZipFile(artifact) as package:
        names = [entry.filename for entry in package.infolist()]
        assert all("\\" not in name for name in names)
        assert not any(name.startswith(MUTABLE_PREFIXES) for name in names)

        target = temp / "wwwroot"
        mutable_files = {
            target / "portal-data" / "student-records.json": b'[{"id":"fixture"}]\n',
            target / "portal-uploads" / "fixture.txt": b"fixture upload\n",
            target / "submissions" / "fixture.txt": b"fixture submission\n",
        }
        for path, payload in mutable_files.items():
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(payload)
        before = {str(path.relative_to(target)): digest(path) for path in mutable_files}
        package.extractall(target)
        after = {str(path.relative_to(target)): digest(path) for path in mutable_files}
        assert before == after

    assert inventory["included_file_count"] == len(
        [name for name in names if not name.endswith("/")]
    )
    assert inventory["sha256"] == digest(artifact)

print("Release artifact tooling test passed")
