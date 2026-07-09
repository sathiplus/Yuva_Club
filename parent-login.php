<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = normalize_yuva_id($_POST['student_id'] ?? '');
    $parentEmail = strtolower(clean_text($_POST['parent_email'] ?? ''));
    $student = find_student($studentId);

    if ($student !== null && strtolower($student['Parent Email'] ?? '') === $parentEmail) {
        $_SESSION['parent_student_id'] = $studentId;
        redirect_to('parent.php');
    }

    redirect_to('parent-login.php?status=error');
}

portal_header('Parent Dashboard Login');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Parent Dashboard</p>
        <h1>Parent Login</h1>
        <p>Parents can view attendance, upcoming presentations, feedback, hours, certificates, recordings, and announcements.</p>
      </div>
      <?php if ($status === 'error'): ?><div class="form-status error">Login failed. Check the Yuva Club ID and parent email.</div><?php endif; ?>
      <form class="form-card" method="post">
        <div class="field">
          <label for="student_id">Yuva Club ID *</label>
          <input id="student_id" name="student_id" type="text" required placeholder="YC2026001">
        </div>
        <div class="field">
          <label for="parent_email">Parent Email *</label>
          <input id="parent_email" name="parent_email" type="email" required>
        </div>
        <button class="button primary" type="submit">View Dashboard</button>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
