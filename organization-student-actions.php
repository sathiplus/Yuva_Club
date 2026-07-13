<?php
require __DIR__ . '/portal-lib.php';

$admin = require_admin_post([YUVA_ROLE_ORGANIZATION_ADMIN]);
$organizationId = normalize_organization_id((string) $admin['organization_id']);
if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID) {
    http_response_code(403);
    exit('Access denied.');
}

$action = clean_text((string) ($_POST['action'] ?? ''));
$status = 'student-error';

if ($action === 'invite_student') {
    $result = upsert_organization_student_membership($admin, [
        'student_email' => (string) ($_POST['student_email'] ?? ''),
        'status' => 'Invited',
        'group' => (string) ($_POST['group'] ?? ''),
        'coach' => (string) ($_POST['coach'] ?? ''),
        'teacher' => (string) ($_POST['teacher'] ?? ''),
        'moderator' => (string) ($_POST['moderator'] ?? ''),
        'notes' => (string) ($_POST['notes'] ?? ''),
        'source' => 'organization_invitation',
        'send_invite' => true,
    ]);
    $status = $result['ok'] ? 'student-invited' : 'student-error';
} elseif ($action === 'link_existing') {
    $result = upsert_organization_student_membership($admin, [
        'student_id' => (string) ($_POST['student_id'] ?? ''),
        'status' => normalize_membership_status((string) ($_POST['status'] ?? 'Active')),
        'group' => (string) ($_POST['group'] ?? ''),
        'coach' => (string) ($_POST['coach'] ?? ''),
        'teacher' => (string) ($_POST['teacher'] ?? ''),
        'moderator' => (string) ($_POST['moderator'] ?? ''),
        'notes' => (string) ($_POST['notes'] ?? ''),
        'source' => 'existing_yuva_student_link',
    ]);
    $status = $result['ok'] ? 'student-linked' : 'student-error';
} elseif ($action === 'update_membership') {
    $membershipKey = (string) ($_POST['membership_key'] ?? '');
    $ok = update_organization_student_membership($admin, $membershipKey, [
        'status' => (string) ($_POST['status'] ?? 'Active'),
        'group' => (string) ($_POST['group'] ?? ''),
        'coach' => (string) ($_POST['coach'] ?? ''),
        'teacher' => (string) ($_POST['teacher'] ?? ''),
        'moderator' => (string) ($_POST['moderator'] ?? ''),
        'transferred_to_organization_id' => (string) ($_POST['transferred_to_organization_id'] ?? ''),
        'notes' => (string) ($_POST['notes'] ?? ''),
    ]);
    $status = $ok ? 'membership-updated' : 'access-denied';
} elseif ($action === 'archive_membership') {
    $membershipKey = (string) ($_POST['membership_key'] ?? '');
    $ok = update_organization_student_membership($admin, $membershipKey, [
        'status' => 'Archived',
        'notes' => (string) ($_POST['notes'] ?? ''),
    ]);
    $status = $ok ? 'membership-archived' : 'access-denied';
} elseif ($action === 'import_csv') {
    $imported = 0;
    $failed = 0;
    if (is_uploaded_file((string) ($_FILES['student_csv']['tmp_name'] ?? ''))) {
        $handle = fopen((string) $_FILES['student_csv']['tmp_name'], 'rb');
        $headers = is_resource($handle) ? fgetcsv($handle) : false;
        if (is_resource($handle) && is_array($headers)) {
            $headers = array_map(static fn ($header): string => strtolower(str_replace([' ', '-'], '_', clean_text((string) $header))), $headers);
            while (($row = fgetcsv($handle)) !== false) {
                $record = [];
                foreach ($headers as $index => $header) {
                    $record[$header] = (string) ($row[$index] ?? '');
                }
                $result = upsert_organization_student_membership($admin, [
                    'student_id' => (string) ($record['yuva_id'] ?? $record['student_id'] ?? ''),
                    'student_email' => (string) ($record['email'] ?? $record['student_email'] ?? ''),
                    'status' => normalize_membership_status((string) ($record['status'] ?? 'Invited')),
                    'group' => (string) ($record['group'] ?? ''),
                    'coach' => (string) ($record['coach'] ?? ''),
                    'teacher' => (string) ($record['teacher'] ?? ''),
                    'moderator' => (string) ($record['moderator'] ?? ''),
                    'notes' => (string) ($record['notes'] ?? ''),
                    'source' => 'csv_import',
                    'send_invite' => (($record['send_invite'] ?? '') === 'yes'),
                ]);
                if ($result['ok']) {
                    $imported++;
                } else {
                    $failed++;
                }
            }
            fclose($handle);
        }
    }
    audit_log_event($admin['id'], $admin['role'], $organizationId, 'organization_student.csv_import', 'student_membership', null, $failed === 0, [
        'imported' => $imported,
        'failed' => $failed,
    ]);
    $status = $imported > 0 ? 'csv-imported' : 'csv-error';
}

redirect_to('organization-admin.php?status=' . rawurlencode($status));
