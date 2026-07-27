<?php
require __DIR__ . '/portal-lib.php';
$student = require_student();
$studentId = $student['Yuva Club ID'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf_token($_POST['csrf_token'] ?? null)) {
    redirect_to('portal.php?status=security-error');
}

$category = clean_text($_POST['topic_category'] ?? '');
$title = clean_text($_POST['topic_title'] ?? '');
$date = clean_text($_POST['presentation_date'] ?? '');
$time = clean_text($_POST['presentation_time'] ?? '');
$topics = yuva_topic_categories();

if ($category === '' || $title === '' || $date === '' || $time === '' || !isset($topics[$category]) || !in_array($title, $topics[$category], true)) {
    redirect_to('portal.php?status=error');
}

if (topic_is_taken($title, $studentId)) {
    redirect_to('portal.php?status=topic-taken');
}

$selections = read_json_file(topic_selections_file());
$existing = $selections[$studentId] ?? [];
$topicChanged = $existing === []
    || ($existing['topic_category'] ?? '') !== $category
    || ($existing['topic_title'] ?? '') !== $title
    || ($existing['presentation_date'] ?? '') !== $date
    || ($existing['presentation_time'] ?? '') !== $time;
$selections[$studentId] = [
    'topic_category' => $category,
    'topic_title' => $title,
    'presentation_date' => $date,
    'presentation_time' => $time,
    'status' => $topicChanged ? 'Pending Admin Review' : ($existing['status'] ?? 'Pending Admin Review'),
    'updated_at' => date('Y-m-d H:i:s'),
];
write_json_file(topic_selections_file(), $selections);
if ($topicChanged) {
    mark_ai_review_stale($studentId, 'Topic Changed');
}
redirect_to('portal.php?status=topic-saved');
