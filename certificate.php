<?php
require __DIR__ . '/portal-lib.php';
$studentId = normalize_yuva_id($_GET['id'] ?? '');
$studentSessionId = normalize_yuva_id(logged_in_student_id() ?? '');
$parentStudentId = normalize_yuva_id($_SESSION['parent_student_id'] ?? '');
$isAdmin = ($_SESSION['admin_logged_in'] ?? false) === true;
if (!$isAdmin && $studentSessionId !== $studentId && $parentStudentId !== $studentId) {
    redirect_to('portal-login.php');
}

$student = find_student($studentId);
if ($student === null) {
    http_response_code(404);
    exit('Student not found.');
}

$selection = read_json_file(topic_selections_file())[$studentId] ?? [];
$record = student_record($studentId);
$rank = approved_rank($record);
$certificateName = rank_definitions()[$rank]['certificate'] ?? 'Certificate of Participation';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificate | Yuva Club</title>
  <link rel="icon" href="assets/logo.png" type="image/png">
  <link rel="stylesheet" href="assets/site.css?v=20260714-public-mobile-nav">
</head>
<body class="certificate-page">
  <main class="certificate-shell">
    <section class="certificate-card">
      <img src="assets/logo.png" alt="Yuva Club logo">
      <p class="eyebrow"><?php echo e($certificateName); ?></p>
      <h1><?php echo e(student_certificate_name($student)); ?></h1>
      <p>has participated in Yuva Club and demonstrated growth through research, presentation, discussion, leadership practice, and peer learning.</p>
      <div class="certificate-details">
        <p><strong>Yuva Club ID:</strong> <?php echo e($studentId); ?></p>
        <p><strong>Membership Group:</strong> <?php echo e(membership_group_label($student)); ?></p>
        <p><strong>Leadership Rank:</strong> <?php echo e($rank); ?></p>
        <p><strong>Presented Topic:</strong> <?php echo e($selection['topic_title'] ?? ''); ?></p>
        <p><strong>Total Sessions Attended:</strong> <?php echo e($record['attendance'] ?? '0'); ?></p>
        <p><strong>Total Volunteer/Leadership Hours:</strong> <?php echo e($record['service_hours'] ?? '0'); ?></p>
      </div>
      <p class="certificate-footer">Yuva Club | YUVA Club | <?php echo date('Y'); ?></p>
      <button class="button primary" onclick="window.print()">Print Certificate</button>
    </section>
  </main>
</body>
</html>
