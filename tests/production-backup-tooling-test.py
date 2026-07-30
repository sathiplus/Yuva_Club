#!/usr/bin/env python3
"""Regression coverage for the consistent production backup gate."""

from __future__ import annotations

import os
import pathlib
import shutil
import subprocess
import tarfile
import tempfile


ROOT = pathlib.Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "tools" / "create-consistent-production-backup.sh"
BASH = os.environ.get("BASH_EXE") or shutil.which("bash")
assert BASH is not None, "bash is required for production backup tooling tests"


def bash_path(path: pathlib.Path) -> str:
    resolved = path.resolve()
    posix = resolved.as_posix()
    if resolved.drive:
        return f"/{resolved.drive[0].lower()}{posix[2:]}"
    return posix


def run_bash(command: str, *, env: dict[str, str] | None = None) -> subprocess.CompletedProcess[str]:
    return subprocess.run(
        [BASH, "-c", command],
        cwd=ROOT,
        env={**os.environ, **(env or {})},
        text=True,
        capture_output=True,
        check=False,
    )


script_text = SCRIPT.read_text(encoding="utf-8")
for required_control in (
    "INCOMPLETE - NOT FOR ROLLBACK",
    "MAX_SNAPSHOT_ATTEMPTS",
    "cmp -s",
    "PIPESTATUS",
    "SOURCE_TAR_EXIT",
    "EXTRACTION_TAR_EXIT",
    "file changed as we read it",
    "file removed before we read it",
    "unexpected EOF",
    "read error",
    "write error",
    "portal-data",
    "portal-uploads",
    "submissions",
    "archive command produced warnings",
    "extracted archive differs from frozen snapshot",
    "OVERALL_VERIFY=PASS",
):
    assert required_control in script_text

with tempfile.TemporaryDirectory(prefix=".backup-tooling-test-", dir=ROOT) as temp_name:
    temp = pathlib.Path(temp_name)
    temp_bash = bash_path(temp)
    script_bash = bash_path(SCRIPT)
    source = temp / "wwwroot"
    source.mkdir()
    (source / "index.html").write_text("release\n", encoding="utf-8")
    (source / "assets").mkdir()
    (source / "assets" / "app.css").write_text("body{}\n", encoding="utf-8")
    for mutable in ("portal-data", "portal-uploads", "submissions"):
        path = source / mutable
        path.mkdir()
        (path / "fixture.txt").write_text(f"{mutable}\n", encoding="utf-8")

    # validate_roots deliberately requires the production prefix. Override only
    # the root validator while exercising the remaining implementation locally.
    backup = temp / "backup"
    command = (
        f"source {script_bash}; "
        "validate_roots() { [[ ! -e \"$2\" ]]; }; "
        f"main {bash_path(source)} {bash_path(backup)}"
    )
    result = run_bash(command)
    assert result.returncode == 0, result.stderr
    assert "CONSISTENT_BACKUP=PASS" in result.stdout
    assert not (backup / "INCOMPLETE-NOT-FOR-ROLLBACK.txt").exists()
    assert (backup / "verification-report.txt").read_text(
        encoding="utf-8"
    ).splitlines()[-1] == "OVERALL_VERIFY=PASS"

    application_archive = backup / "archives" / "current-production-application.tar.gz"
    with tarfile.open(application_archive, "r:gz") as archive:
        names = {name.removeprefix("./") for name in archive.getnames()}
    assert "index.html" in names
    assert "assets/app.css" in names
    assert not any(
        name == mutable or name.startswith(f"{mutable}/")
        for mutable in ("portal-data", "portal-uploads", "submissions")
        for name in names
    )
    for mutable in ("portal-data", "portal-uploads", "submissions"):
        checksum_lines = (backup / "manifests" / f"{mutable}.sha256").read_text(
            encoding="utf-8"
        )
        assert "fixture.txt" in checksum_lines
        with tarfile.open(backup / "archives" / f"{mutable}.tar.gz", "r:gz") as archive:
            assert "fixture.txt" in {
                name.removeprefix("./") for name in archive.getnames()
            }

    clean_copy = temp / "clean-copy"
    clean_pipeline = run_bash(
        f"""
        source {script_bash}
        copy_snapshot_once \
          {bash_path(source)} \
          {bash_path(clean_copy)} \
          application
        """
    )
    assert clean_pipeline.returncode == 0, clean_pipeline.stderr
    clean_status = pathlib.Path(f"{clean_copy}.copy-diagnostics/pipeline-status.txt")
    assert clean_status.read_text(encoding="utf-8").splitlines() == [
        "SOURCE_TAR_EXIT=0",
        "EXTRACTION_TAR_EXIT=0",
    ]

    inside_source = source / "backup"
    rejected = run_bash(
        f"source {script_bash}; validate_roots "
        f"{bash_path(source)} {bash_path(inside_source)}"
    )
    assert rejected.returncode != 0
    assert "cannot be inside source root" in rejected.stderr

    unstable = run_bash(
        f"""
        source {script_bash}
        counter=0
        copy_snapshot_once() {{
          counter=$((counter + 1))
          mkdir -p "$2"
          printf '%s\\n' "$counter" > "$2/changing.txt"
        }}
        MAX_SNAPSHOT_ATTEMPTS=3
        stabilize_snapshot ignored {temp_bash}/unstable unstable application
        """
    )
    assert unstable.returncode != 0
    assert "source remained unstable" in unstable.stderr

    real_tar = shutil.which("tar")
    assert real_tar is not None
    real_tar_bash = bash_path(pathlib.Path(real_tar))

    source_warning_tar = temp / "source-warning-tar"
    source_warning_tar.write_text(
        "#!/usr/bin/env bash\n"
        "if [[ \" $* \" == *\" -cf - \"* ]]; then\n"
        "  \"$REAL_TAR\" \"$@\"\n"
        "  echo 'tar: ./assets: file changed as we read it' >&2\n"
        "  exit 1\n"
        "fi\n"
        "exec \"$REAL_TAR\" \"$@\"\n",
        encoding="utf-8",
    )
    source_warning_tar.chmod(0o755)
    source_failure_backup = temp / "source-failure-backup"
    source_pipeline_failure = run_bash(
        f"""
        source {script_bash}
        validate_roots() {{ [[ ! -e "$2" ]]; }}
        TAR_BIN={bash_path(source_warning_tar)}
        main {bash_path(source)} {bash_path(source_failure_backup)}
        """,
        env={"REAL_TAR": real_tar_bash},
    )
    assert source_pipeline_failure.returncode != 0
    assert "CONSISTENT_BACKUP=PASS" not in source_pipeline_failure.stdout
    assert (
        source_failure_backup / "INCOMPLETE-NOT-FOR-ROLLBACK.txt"
    ).read_text(encoding="utf-8").strip() == "INCOMPLETE - NOT FOR ROLLBACK"
    assert not (source_failure_backup / "verification-report.txt").exists()
    assert not (
        source_failure_backup
        / "archives"
        / "current-production-application.tar.gz"
    ).exists()
    source_diagnostics = (
        source_failure_backup
        / ".snapshot-work"
        / "application-pass-1.copy-diagnostics"
    )
    assert (source_diagnostics / "pipeline-status.txt").read_text(
        encoding="utf-8"
    ).splitlines() == [
        "SOURCE_TAR_EXIT=1",
        "EXTRACTION_TAR_EXIT=0",
    ]
    assert "file changed as we read it" in (
        source_diagnostics / "source-tar.stderr"
    ).read_text(encoding="utf-8")
    assert (source_diagnostics / "extraction-tar.stderr").exists()

    warning_only_tar = temp / "warning-only-tar"
    warning_only_tar.write_text(
        "#!/usr/bin/env bash\n"
        "if [[ \" $* \" == *\" -cf - \"* ]]; then\n"
        "  \"$REAL_TAR\" \"$@\"\n"
        "  status=$?\n"
        "  echo 'tar: ./assets: file changed as we read it' >&2\n"
        "  exit \"$status\"\n"
        "fi\n"
        "exec \"$REAL_TAR\" \"$@\"\n",
        encoding="utf-8",
    )
    warning_only_tar.chmod(0o755)
    warning_only_copy = temp / "warning-only-copy"
    warning_only_failure = run_bash(
        f"""
        source {script_bash}
        TAR_BIN={bash_path(warning_only_tar)}
        copy_snapshot_once \
          {bash_path(source)} \
          {bash_path(warning_only_copy)} \
          application
        """,
        env={"REAL_TAR": real_tar_bash},
    )
    assert warning_only_failure.returncode != 0
    warning_only_diagnostics = pathlib.Path(
        f"{warning_only_copy}.copy-diagnostics"
    )
    assert (warning_only_diagnostics / "pipeline-status.txt").read_text(
        encoding="utf-8"
    ).splitlines() == [
        "SOURCE_TAR_EXIT=0",
        "EXTRACTION_TAR_EXIT=0",
    ]
    assert "file changed as we read it" in (
        warning_only_diagnostics / "source-tar.stderr"
    ).read_text(encoding="utf-8")
    assert "fatal tar warning" in warning_only_failure.stderr

    extraction_failure_tar = temp / "extraction-failure-tar"
    extraction_failure_tar.write_text(
        "#!/usr/bin/env bash\n"
        "if [[ \" $* \" == *\" -xf - \"* ]]; then\n"
        "  cat >/dev/null\n"
        "  echo 'tar: write error' >&2\n"
        "  exit 2\n"
        "fi\n"
        "exec \"$REAL_TAR\" \"$@\"\n",
        encoding="utf-8",
    )
    extraction_failure_tar.chmod(0o755)
    extraction_copy = temp / "extraction-failure-copy"
    extraction_pipeline_failure = run_bash(
        f"""
        source {script_bash}
        TAR_BIN={bash_path(extraction_failure_tar)}
        copy_snapshot_once \
          {bash_path(source)} \
          {bash_path(extraction_copy)} \
          application
        """,
        env={"REAL_TAR": real_tar_bash},
    )
    assert extraction_pipeline_failure.returncode != 0
    extraction_diagnostics = pathlib.Path(
        f"{extraction_copy}.copy-diagnostics"
    )
    assert (extraction_diagnostics / "pipeline-status.txt").read_text(
        encoding="utf-8"
    ).splitlines() == [
        "SOURCE_TAR_EXIT=0",
        "EXTRACTION_TAR_EXIT=2",
    ]
    assert (extraction_diagnostics / "source-tar.stderr").exists()
    assert "write error" in (
        extraction_diagnostics / "extraction-tar.stderr"
    ).read_text(encoding="utf-8")

    fake_tar = temp / "warning-tar"
    fake_tar.write_text(
        "#!/usr/bin/env bash\n"
        "echo 'tar: file changed as we read it' >&2\n"
        "exit 0\n",
        encoding="utf-8",
    )
    fake_tar.chmod(0o755)
    warning_archive = run_bash(
        f"""
        source {script_bash}
        TAR_BIN={bash_path(fake_tar)}
        mkdir -p {temp_bash}/warning-source
        : > {temp_bash}/warning.manifest
        create_verified_archive \
          {temp_bash}/warning-source \
          {temp_bash}/warning.tar.gz \
          {temp_bash}/warning.manifest \
          {temp_bash}/warning-extracted
        """
    )
    assert warning_archive.returncode != 0
    assert "archive command produced warnings" in warning_archive.stderr

    failing_tar = temp / "failing-tar"
    failing_tar.write_text("#!/usr/bin/env bash\nexit 2\n", encoding="utf-8")
    failing_tar.chmod(0o755)
    failed_archive = run_bash(
        f"""
        source {script_bash}
        TAR_BIN={bash_path(failing_tar)}
        mkdir -p {temp_bash}/failure-source
        : > {temp_bash}/failure.manifest
        create_verified_archive \
          {temp_bash}/failure-source \
          {temp_bash}/failure.tar.gz \
          {temp_bash}/failure.manifest \
          {temp_bash}/failure-extracted
        """
    )
    assert failed_archive.returncode != 0
    assert "archive command failed" in failed_archive.stderr

    mismatch_source = temp / "mismatch-source"
    mismatch_source.mkdir()
    (mismatch_source / "actual.txt").write_text("actual\n", encoding="utf-8")
    wrong_manifest = temp / "wrong.manifest"
    wrong_manifest.write_text("not the snapshot manifest\n", encoding="utf-8")
    mismatched_archive = run_bash(
        f"""
        source {script_bash}
        create_verified_archive \
          {bash_path(mismatch_source)} \
          {temp_bash}/mismatch.tar.gz \
          {bash_path(wrong_manifest)} \
          {temp_bash}/mismatch-extracted
        """
    )
    assert mismatched_archive.returncode != 0
    assert "extracted archive differs" in mismatched_archive.stderr

print("Production backup tooling test passed")
