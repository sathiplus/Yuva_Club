<?php
require __DIR__ . '/portal-lib.php';
require_admin();

$students = portal_students();
$status = $_GET['status'] ?? '';

portal_header('Signup Students');
?>
<main>
  <section class="band">
    <div class="section-head">
      <p class="eyebrow">Admin</p>
      <h1>Signup Students</h1>
      <p>View all student, parent, contact, emergency, schedule, interest, and agreement information submitted through the sign in form.</p>
      <p><a class="button ghost" href="admin.php">Admin Dashboard</a></p>
    </div>

    <?php if ($status === 'student-saved'): ?>
      <div class="form-status success">Signup record updated.</div>
    <?php elseif ($status === 'student-error'): ?>
      <div class="form-status error">The signup record could not be updated.</div>
    <?php endif; ?>

    <div class="portal-table-wrap">
      <table class="portal-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Parent/Guardian</th>
            <th>Student Contact</th>
            <th>Participation</th>
            <th>Edit</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $studentId => $student): ?>
            <tr>
              <td>
                <strong><?php echo e(student_display_name($student)); ?></strong><br>
                <span><?php echo e($studentId); ?></span><br>
                <span>DOB: <?php echo e($student['Date of Birth'] ?? ''); ?></span><br>
                <span>Age: <?php echo e($student['Age'] ?? ''); ?></span><br>
                <span>Group: <?php echo e($student['Program Group'] ?? ''); ?></span><br>
                <span>Grade: <?php echo e($student['Grade'] ?? ''); ?></span><br>
                <span>School: <?php echo e($student['School'] ?? ''); ?></span><br>
                <span><?php echo e($student['City/State'] ?? ''); ?></span>
              </td>
              <td>
                <strong><?php echo e($student['Parent/Guardian Name'] ?? ''); ?></strong><br>
                <span><?php echo e($student['Relationship'] ?? ''); ?></span><br>
                <span><?php echo e($student['Parent Email'] ?? ''); ?></span><br>
                <span><?php echo e($student['Parent Phone Number'] ?? ''); ?></span>
              </td>
              <td>
                <span>Student Email: <?php echo e($student['Student Email'] ?? ''); ?></span><br>
                <span>Student Phone: <?php echo e($student['Student Phone Number'] ?? ''); ?></span><br>
                <span>WhatsApp: <?php echo e($student['WhatsApp Username / Number'] ?? (($student['WhatsApp Username'] ?? '') . ' ' . ($student['WhatsApp Phone Number'] ?? ''))); ?></span>
              </td>
              <td>
                <p><strong>Interests:</strong> <?php echo e($student['Interests'] ?? ''); ?></p>
                <p><strong>Why Join:</strong> <?php echo e($student['Why Join'] ?? ''); ?></p>
                <p><strong>Presentation Experience:</strong> <?php echo e($student['Presentation Experience'] ?? ''); ?></p>
                <p><strong>Topics:</strong> <?php echo e($student['Presentation Topics'] ?? ''); ?></p>
                <p><strong>Schedule:</strong> <?php echo e($student['Preferred Schedule'] ?? ''); ?></p>
              </td>
              <td>
                <a class="button primary" href="admin-student-edit.php?id=<?php echo e($studentId); ?>">Edit</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if ($students === []): ?>
            <tr><td colspan="5">No signup students found yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
