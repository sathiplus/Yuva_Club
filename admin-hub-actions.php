<?php
require __DIR__ . '/portal-lib.php';
require_admin();

$settings = [
    'junior_session_title' => clean_text($_POST['junior_session_title'] ?? 'Junior Yuva Club Session'),
    'junior_session_date' => clean_text($_POST['junior_session_date'] ?? ''),
    'junior_session_start' => clean_text($_POST['junior_session_start'] ?? ''),
    'junior_session_end' => clean_text($_POST['junior_session_end'] ?? ''),
    'junior_session_status' => clean_text($_POST['junior_session_status'] ?? 'Closed'),
    'junior_zoom_url' => trim((string) ($_POST['junior_zoom_url'] ?? '')),
    'junior_zoom_meeting_id' => clean_text($_POST['junior_zoom_meeting_id'] ?? ''),
    'junior_zoom_password' => clean_text($_POST['junior_zoom_password'] ?? ''),
    'junior_scheduler_embed' => trim((string) ($_POST['junior_scheduler_embed'] ?? '')),
    'senior_session_title' => clean_text($_POST['senior_session_title'] ?? 'Senior Yuva Club Session'),
    'senior_session_date' => clean_text($_POST['senior_session_date'] ?? ''),
    'senior_session_start' => clean_text($_POST['senior_session_start'] ?? ''),
    'senior_session_end' => clean_text($_POST['senior_session_end'] ?? ''),
    'senior_session_status' => clean_text($_POST['senior_session_status'] ?? 'Closed'),
    'senior_zoom_url' => trim((string) ($_POST['senior_zoom_url'] ?? '')),
    'senior_zoom_meeting_id' => clean_text($_POST['senior_zoom_meeting_id'] ?? ''),
    'senior_zoom_password' => clean_text($_POST['senior_zoom_password'] ?? ''),
    'senior_scheduler_embed' => trim((string) ($_POST['senior_scheduler_embed'] ?? '')),
    'announcements' => trim((string) ($_POST['announcements'] ?? '')),
    'recordings' => trim((string) ($_POST['recordings'] ?? '')),
    'resources' => trim((string) ($_POST['resources'] ?? '')),
    'updated_at' => date('Y-m-d H:i:s'),
];

write_json_file(hub_settings_file(), $settings);
redirect_to('admin.php?status=hub-saved');
