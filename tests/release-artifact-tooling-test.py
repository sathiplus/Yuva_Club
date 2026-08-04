#!/usr/bin/env python3
"""Release packaging regression test, including mutable-overlay preservation."""

from __future__ import annotations

import hashlib
import importlib.util
import json
import pathlib
import re
import subprocess
import sys
import tempfile
import zipfile


ROOT = pathlib.Path(__file__).resolve().parents[1]
BUILDER = ROOT / "tools" / "build-release-artifact.py"
EXCLUSIONS = ROOT / "tools" / "release-artifact-exclusions.txt"
WORKFLOW = ROOT / ".github" / "workflows" / "main_yuvaclub.yml"
MUTABLE_PREFIXES = ("portal-data/", "portal-uploads/", "submissions/")
REGISTRATION_HANDLER = "submit-registration.php"
BACKUP_PATH_PATTERN = re.compile(
    r"^/home/data/yuva-release-1\.0\.1-[0-9]{8}T[0-9]{6}Z$"
)


def load_builder():
    specification = importlib.util.spec_from_file_location(
        "release_artifact_builder", BUILDER
    )
    assert specification is not None
    assert specification.loader is not None
    module = importlib.util.module_from_spec(specification)
    specification.loader.exec_module(module)
    return module


def digest(path: pathlib.Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


builder = load_builder()
exclusion_rules = builder.read_exclusions(EXCLUSIONS)
workflow = WORKFLOW.read_text(encoding="utf-8")
assert not builder.matches_exclusion(REGISTRATION_HANDLER, exclusion_rules)
assert (
    '[[ "$BACKUP_ROOT" =~ '
    r"^/home/data/yuva-release-1\.0\.1-[0-9]{8}T[0-9]{6}Z$ ]]"
) in workflow
assert BACKUP_PATH_PATTERN.fullmatch(
    "/home/data/yuva-release-1.0.1-20260730T021500Z"
)
for rejected_backup_path in (
    "/tmp/yuva-release-1.0.1-20260730T021500Z",
    "/home/data/yuva-release-1.0.1",
    "/home/data/yuva-release-1.0.1-2026-07-30T02:15:00Z",
    "/home/data/yuva-release-1.0.1-20260730T021500Z/extra",
    "/home/data/../yuva-release-1.0.1-20260730T021500Z",
    "/home/data/yuva-release-1.0.1-20260730T021500Z;rm",
    "/home/data/yuva-rc1-release-gate-20260730T021500Z",
):
    assert BACKUP_PATH_PATTERN.fullmatch(rejected_backup_path) is None

for production_control in (
    "group: yuvaclub-production",
    "cancel-in-progress: false",
    "environment: production",
    "git merge-base --is-ancestor",
    "OVERALL_VERIFY=PASS",
    "sha256sum -c",
    "--clean false",
    "retention-days: 90",
    "Public production smoke tests",
    "/submit-registration.php?health=1",
    ".database_configured == true",
    ".database_connected == true",
):
    assert production_control in workflow

for private_name in (
    ".env",
    ".env.local",
    ".env.production",
    ".env.staging",
    ".env.test",
):
    assert builder.matches_exclusion(private_name, exclusion_rules)
for unrelated_name in (
    "environment.md",
    "environmental-report.txt",
    "config-env.json",
    "my.env.local.txt",
):
    assert not builder.matches_exclusion(unrelated_name, exclusion_rules)


with tempfile.TemporaryDirectory() as temp_name:
    temp = pathlib.Path(temp_name)
    fixture_repo = temp / "environment-fixture"
    fixture_repo.mkdir()
    for name in (
        ".env",
        ".env.local",
        ".env.production",
        ".env.staging",
        ".env.test",
        "environment.md",
        "environmental-report.txt",
        "config-env.json",
        "my.env.local.txt",
    ):
        (fixture_repo / name).write_text(f"{name}\n", encoding="utf-8")
    subprocess.run(["git", "init"], cwd=fixture_repo, check=True)
    subprocess.run(["git", "add", "."], cwd=fixture_repo, check=True)
    subprocess.run(
        [
            "git",
            "-c",
            "user.name=YUVA Release Test",
            "-c",
            "user.email=release-test@example.invalid",
            "commit",
            "-m",
            "fixture",
        ],
        cwd=fixture_repo,
        check=True,
    )
    environment_artifact = temp / "environment-release.zip"
    environment_inventory = temp / "environment-inventory.json"
    subprocess.run(
        [
            sys.executable,
            str(BUILDER),
            "--commit",
            "HEAD",
            "--output",
            str(environment_artifact),
            "--exclusions",
            str(EXCLUSIONS),
            "--inventory",
            str(environment_inventory),
        ],
        cwd=fixture_repo,
        check=True,
    )
    with zipfile.ZipFile(environment_artifact) as package:
        environment_names = set(package.namelist())
    assert not {
        ".env",
        ".env.local",
        ".env.production",
        ".env.staging",
        ".env.test",
    } & environment_names
    assert {
        "environment.md",
        "environmental-report.txt",
        "config-env.json",
        "my.env.local.txt",
    } <= environment_names

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
        assert REGISTRATION_HANDLER in names
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
    assert REGISTRATION_HANDLER in inventory["included_files"]
    assert inventory["sha256"] == digest(artifact)

print("Release artifact tooling test passed")
