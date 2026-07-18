<?php
require __DIR__ . '/portal-lib.php';

$status = $_GET['status'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = normalize_yuva_id($_POST['student_id'] ?? '');
    $dateOfBirth = clean_text($_POST['date_of_birth'] ?? '');
    $student = find_student($studentId);

    if ($student !== null && ($student['Date of Birth'] ?? '') === $dateOfBirth) {
        session_regenerate_id(true);
        $_SESSION['student_id'] = $studentId;
        redirect_to('portal.php');
    }

    redirect_to('portal-login.php?status=error');
}

portal_header('Student Portal Login');
?>
<main>
  <section class="band">
    <div class="form-shell portal-narrow">
      <div class="section-head">
        <p class="eyebrow">Student Portal</p>
        <h1>Student Login</h1>
        <p>Students can log in with their Yuva Club ID and date of birth after registration.</p>
      </div>

      <?php if ($status === 'error'): ?>
        <div class="form-status error">Login failed. Check the Yuva Club ID and date of birth.</div>
      <?php elseif ($status === 'missing'): ?>
        <div class="form-status error">Your registration record was not found. Please contact the Yuva Club admin.</div>
      <?php endif; ?>

      <form class="form-card" method="post">
        <div class="field">
          <label for="student_id">Yuva Club ID *</label>
          <input id="student_id" name="student_id" type="text" required placeholder="YC2026001">
        </div>
        <div class="field">
          <label for="date_of_birth">Date of Birth *</label>
          <input id="date_of_birth" name="date_of_birth" type="date" required>
        </div>
        <button class="button primary" type="submit">Log In</button>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
