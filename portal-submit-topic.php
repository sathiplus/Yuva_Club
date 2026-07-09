<?php
require __DIR__ . '/portal-lib.php';
$student = require_student();
$studentId = $student['Yuva Club ID'];

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
$selections[$studentId] = [
    'topic_category' => $category,
    'topic_title' => $title,
    'presentation_date' => $date,
    'presentation_time' => $time,
    'status' => $selections[$studentId]['status'] ?? 'Pending Admin Review',
    'updated_at' => date('Y-m-d H:i:s'),
];
write_json_file(topic_selections_file(), $selections);
redirect_to('portal.php?status=topic-saved');
