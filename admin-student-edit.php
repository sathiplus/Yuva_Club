<?php
require __DIR__ . '/portal-lib.php';
require_admin();

$studentId = normalize_yuva_id($_GET['id'] ?? $_POST['student_id'] ?? '');
$student = $studentId !== '' ? find_registration_row($studentId) : null;

if ($student === null) {
    portal_header('Edit Signup');
    echo '<main><section class="band"><div class="form-status error">Student signup record not found.</div><p><a class="button ghost" href="admin-students.php">Back to Signup Students</a></p></section></main>';
    portal_footer();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updates = [];
    foreach (editable_registration_fields() as $fields) {
        foreach ($fields as $field) {
            $updates[$field] = $_POST['fields'][$field] ?? '';
        }
    }

    $saved = update_registration_row($studentId, $updates);
    redirect_to('admin-students.php?status=' . ($saved ? 'student-saved' : 'student-error'));
}

portal_header('Edit Signup');
?>
<main>
  <section class="band">
    <div class="form-shell">
      <div class="section-head">
        <p class="eyebrow">Edit Signup</p>
        <h1><?php echo e(student_display_name($student)); ?></h1>
        <p>Yuva Club ID: <?php echo e($studentId); ?></p>
        <p><a class="button ghost" href="admin-students.php">Back to Signup Students</a></p>
      </div>

      <form class="form-card" method="post">
        <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
        <?php foreach (editable_registration_fields() as $group => $fields): ?>
          <h2><?php echo e($group); ?></h2>
          <div class="field-grid">
            <?php foreach ($fields as $field): ?>
              <div class="field">
                <label for="<?php echo e(str_replace(' ', '_', $field)); ?>"><?php echo e($field); ?></label>
                <?php if (in_array($field, ['Why Join', 'Presentation Topics', 'Preferred Schedule', 'Suggestions', 'Interests'], true)): ?>
                  <textarea id="<?php echo e(str_replace(' ', '_', $field)); ?>" name="fields[<?php echo e($field); ?>]"><?php echo e($student[$field] ?? ''); ?></textarea>
                <?php else: ?>
                  <input id="<?php echo e(str_replace(' ', '_', $field)); ?>" name="fields[<?php echo e($field); ?>]" type="text" value="<?php echo e($student[$field] ?? ''); ?>">
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <button class="button primary" type="submit">Save Signup Record</button>
      </form>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
