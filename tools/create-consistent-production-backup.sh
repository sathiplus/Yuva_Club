#!/usr/bin/env bash
set -euo pipefail

MAX_SNAPSHOT_ATTEMPTS="${MAX_SNAPSHOT_ATTEMPTS:-4}"
TAR_BIN="${TAR_BIN:-tar}"

fail() {
  printf 'BACKUP_ERROR=%s\n' "$*" >&2
  return 1
}

manifest_tree() {
  local root="$1"
  local output="$2"

  (
    cd "$root"
    while IFS= read -r -d '' path; do
      local relative="${path#./}"
      if [[ -L "$path" ]]; then
        printf 'l\t%q\t%s\t%s\n' \
          "$relative" "$(stat -c '%a' "$path")" "$(readlink "$path")"
      elif [[ -f "$path" ]]; then
        printf 'f\t%q\t%s\t%s\t%s\n' \
          "$relative" "$(stat -c '%a' "$path")" "$(stat -c '%s' "$path")" \
          "$(sha256sum "$path" | cut -d' ' -f1)"
      elif [[ -d "$path" ]]; then
        printf 'd\t%q\t%s\n' "$relative" "$(stat -c '%a' "$path")"
      else
        fail "unsupported file type in snapshot: $relative"
        exit 1
      fi
    done < <(find . -mindepth 1 -print0 | sort -z)
  ) > "$output"
}

checksum_manifest() {
  local root="$1"
  local output="$2"

  (
    cd "$root"
    find . -type f -print0 | sort -z | xargs -0 -r sha256sum
  ) > "$output"
}

copy_snapshot_once() {
  local source="$1"
  local destination="$2"
  local mode="$3"
  local -a excludes=()

  if [[ "$mode" == "application" ]]; then
    excludes=(
      --exclude='./portal-data'
      --exclude='./portal-uploads'
      --exclude='./submissions'
      --exclude='./LogFiles'
      --exclude='./logs'
      --exclude='./tmp'
      --exclude='./temp'
      --exclude='./.cache'
      --exclude='./cache'
      --exclude='./sessions'
      --exclude='./.deployment'
      --exclude='./deployments'
      --exclude='./artifacts'
    )
  fi

  mkdir -p "$destination"
  (
    cd "$source"
    "$TAR_BIN" "${excludes[@]}" -cf - .
  ) | (
    cd "$destination"
    "$TAR_BIN" -xf -
  )
}

stabilize_snapshot() {
  local source="$1"
  local workspace="$2"
  local label="$3"
  local mode="$4"
  local previous_snapshot=""
  local previous_manifest=""
  local attempt

  for ((attempt = 1; attempt <= MAX_SNAPSHOT_ATTEMPTS; attempt++)); do
    local snapshot="$workspace/${label}-pass-${attempt}"
    local manifest="$workspace/${label}-pass-${attempt}.manifest"
    copy_snapshot_once "$source" "$snapshot" "$mode"
    manifest_tree "$snapshot" "$manifest"

    if [[ -n "$previous_manifest" ]] && cmp -s "$previous_manifest" "$manifest"; then
      printf '%s\n' "$snapshot"
      return 0
    fi

    if [[ -n "$previous_snapshot" ]]; then
      rm -rf -- "$previous_snapshot"
    fi
    previous_snapshot="$snapshot"
    previous_manifest="$manifest"
  done

  fail "source remained unstable after ${MAX_SNAPSHOT_ATTEMPTS} snapshots: $source"
}

create_verified_archive() {
  local snapshot="$1"
  local archive="$2"
  local expected_manifest="$3"
  local verification_root="$4"
  local stderr_file="$archive.stderr"

  if ! "$TAR_BIN" -czf "$archive" -C "$snapshot" . 2> "$stderr_file"; then
    fail "archive command failed: $archive"
    return 1
  fi
  if [[ -s "$stderr_file" ]]; then
    fail "archive command produced warnings: $archive"
    return 1
  fi
  rm -f -- "$stderr_file"

  mkdir -p "$verification_root"
  if ! "$TAR_BIN" -xzf "$archive" -C "$verification_root"; then
    fail "archive extraction failed: $archive"
    return 1
  fi

  local extracted_manifest="$verification_root.manifest"
  manifest_tree "$verification_root" "$extracted_manifest"
  if ! cmp -s "$expected_manifest" "$extracted_manifest"; then
    fail "extracted archive differs from frozen snapshot: $archive"
    return 1
  fi
}

validate_roots() {
  local source="$1"
  local backup="$2"
  local source_real
  local backup_real

  source_real="$(realpath "$source")"
  backup_real="$(realpath -m "$backup")"
  case "$backup_real/" in
    "$source_real/"*)
      fail "backup destination cannot be inside source root"
      ;;
  esac
  [[ "$backup_real" =~ ^/home/data/yuva-release-1\.0\.1-[0-9]{8}T[0-9]{6}Z$ ]] \
    || fail "backup destination must use the exact approved Release 1.0.1 path"
  [[ ! -e "$backup_real" ]] || fail "backup destination already exists"
}

main() {
  [[ "$#" -eq 2 ]] || {
    printf 'Usage: %s SOURCE_ROOT BACKUP_ROOT\n' "$0" >&2
    return 2
  }

  local source="$1"
  local backup="$2"
  validate_roots "$source" "$backup"

  umask 077
  mkdir -p "$backup/archives" "$backup/manifests" "$backup/inventory"
  printf 'INCOMPLETE - NOT FOR ROLLBACK\n' > "$backup/INCOMPLETE-NOT-FOR-ROLLBACK.txt"

  local workspace="$backup/.snapshot-work"
  mkdir -p "$workspace"

  local app_snapshot
  app_snapshot="$(stabilize_snapshot "$source" "$workspace" application application)"
  manifest_tree "$app_snapshot" "$backup/manifests/application.snapshot.manifest"
  create_verified_archive \
    "$app_snapshot" \
    "$backup/archives/current-production-application.tar.gz" \
    "$backup/manifests/application.snapshot.manifest" \
    "$workspace/application-extracted"

  local mutable
  for mutable in portal-data portal-uploads submissions; do
    [[ -d "$source/$mutable" ]] || fail "required mutable path is missing: $mutable"
    local mutable_snapshot
    mutable_snapshot="$(stabilize_snapshot \
      "$source/$mutable" "$workspace" "$mutable" mutable)"
    manifest_tree \
      "$mutable_snapshot" "$backup/manifests/$mutable.snapshot.manifest"
    checksum_manifest "$mutable_snapshot" "$backup/manifests/$mutable.sha256"
    create_verified_archive \
      "$mutable_snapshot" \
      "$backup/archives/$mutable.tar.gz" \
      "$backup/manifests/$mutable.snapshot.manifest" \
      "$workspace/$mutable-extracted"
  done

  (
    cd "$backup"
    sha256sum archives/*.tar.gz > manifests/backup-artifacts.sha256
    sha256sum -c manifests/backup-artifacts.sha256 >/dev/null
  )

  rm -rf -- "$workspace"
  printf '%s\n' \
    'APPLICATION_SNAPSHOT=PASS' \
    'MUTABLE_SNAPSHOTS=PASS' \
    'ARCHIVE_EXTRACTION=PASS' \
    'MANIFEST_MATCH=PASS' \
    'OVERALL_VERIFY=PASS' > "$backup/verification-report.txt"
  rm -f -- "$backup/INCOMPLETE-NOT-FOR-ROLLBACK.txt"
  printf 'CONSISTENT_BACKUP=PASS\n'
}

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
  main "$@"
fi
