#!/usr/bin/env python3
"""Regression coverage for fail-closed isolated validation tooling."""

from __future__ import annotations

import hashlib
import json
import pathlib
import subprocess
import sys
import tempfile


ROOT = pathlib.Path(__file__).resolve().parents[1]
RUNNER = ROOT / "tools" / "run-isolated-validation.py"


def digest(path: pathlib.Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def run_case(
    source: pathlib.Path,
    temp_parent: pathlib.Path,
    logs: pathlib.Path,
    suite: str,
) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        [
            sys.executable,
            str(RUNNER),
            "--source",
            str(source),
            "--php",
            sys.executable,
            "--suite",
            suite,
            "--log-directory",
            str(logs),
            "--temp-parent",
            str(temp_parent),
        ],
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )


with tempfile.TemporaryDirectory() as outer_name:
    outer = pathlib.Path(outer_name)
    source = outer / "source"
    temp_parent = outer / "temporary"
    logs = outer / "logs"
    temp_parent.mkdir()
    for directory in ("portal-data", "portal-uploads", "submissions"):
        mutable = source / directory
        mutable.mkdir(parents=True)
        (mutable / "live-sentinel.txt").write_text(
            f"{directory} live data\n", encoding="utf-8"
        )
    (source / "write_fixture.py").write_text(
        "import os, pathlib, sys\n"
        "root = pathlib.Path(os.environ['YUVA_TEST_ISOLATED_ROOT'])\n"
        "assert root == pathlib.Path.cwd()\n"
        "(root / 'portal-data' / 'fixture.json').write_text('{}\\n')\n"
        "(root / 'portal-uploads' / 'fixture.bin').write_bytes(b'fixture')\n"
        "(root / 'submissions' / 'fixture.csv').write_text('fixture\\n')\n"
        "raise SystemExit(0)\n",
        encoding="utf-8",
    )
    before = {
        str(path.relative_to(source)): digest(path)
        for path in source.rglob("live-sentinel.txt")
    }

    success = run_case(source, temp_parent, logs / "success", "write_fixture.py")
    assert success.returncode == 0, success.stderr
    assert not list(temp_parent.iterdir())
    after_success = {
        str(path.relative_to(source)): digest(path)
        for path in source.rglob("live-sentinel.txt")
    }
    assert before == after_success

    failing_script = source / "write_fixture.py"
    failing_script.write_text(
        failing_script.read_text(encoding="utf-8").replace(
            "SystemExit(0)", "SystemExit(7)"
        ),
        encoding="utf-8",
    )
    failure = run_case(source, temp_parent, logs / "failure", "write_fixture.py")
    assert failure.returncode == 7
    assert not list(temp_parent.iterdir())
    after_failure = {
        str(path.relative_to(source)): digest(path)
        for path in source.rglob("live-sentinel.txt")
    }
    assert before == after_failure
    failure_log = json.loads(
        next((logs / "failure").glob("*.json")).read_text(encoding="utf-8")
    )
    assert failure_log["exit_code"] == 7

    unsafe = subprocess.run(
        [
            sys.executable,
            str(RUNNER),
            "--source",
            str(source),
            "--php",
            sys.executable,
            "--suite",
            "write_fixture.py",
            "--log-directory",
            str(logs / "unsafe"),
            "--temp-parent",
            "/home/site/wwwroot",
        ],
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        check=False,
    )
    assert unsafe.returncode != 0
    assert "production-like temporary root" in unsafe.stderr

print("Isolated validation tooling test passed")
