<?php
require __DIR__ . '/portal-lib.php';
$admin = require_admin([YUVA_ROLE_ORGANIZATION_ADMIN]);

$organizationId = normalize_organization_id((string) $admin['organization_id']);
if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID) {
    http_response_code(403);
    exit('Access denied.');
}

$students = array_filter(
    portal_students(),
    static fn (array $student): bool => student_organization_id($student) === $organizationId
);
$records = read_json_file(portal_records_file());
$reports = safety_reports();
$orgReports = array_filter(
    $reports,
    static fn ($report): bool => is_array($report) && student_organization_id(find_student((string) ($report['student_id'] ?? '')) ?? []) === $organizationId
);

portal_header('Organization Admin Dashboard');
?>
<main>
  <section class="band">
    <div class="section-head">
      <p class="eyebrow">Organization Administrator Dashboard</p>
      <h1><?php echo e($organizationId); ?></h1>
      <p>Manage only the YUVA Club records assigned to your organization.</p>
      <p><a class="button ghost" href="portal-logout.php">Log Out</a></p>
    </div>

    <div class="dashboard-grid">
      <div class="metric-card">
        <span>Students</span>
        <strong><?php echo count($students); ?></strong>
      </div>
      <div class="metric-card">
        <span>Safety Reports</span>
        <strong><?php echo count($orgReports); ?></strong>
      </div>
      <div class="metric-card">
        <span>Role</span>
        <strong><?php echo e($admin['role']); ?></strong>
      </div>
    </div>

    <section class="form-card">
      <h2>Organization Students</h2>
      <?php if ($students === []): ?>
        <p>No student records are assigned to this organization yet.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>YUVA ID</th>
                <th>Name</th>
                <th>Program</th>
                <th>School</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $studentId => $student): ?>
                <?php $record = $records[$studentId] ?? []; ?>
                <tr>
                  <td><?php echo e((string) $studentId); ?></td>
                  <td><?php echo e(student_display_name($student)); ?></td>
                  <td><?php echo e((string) ($student['Program Group'] ?? '')); ?></td>
                  <td><?php echo e((string) ($student['School'] ?? '')); ?></td>
                  <td><?php echo e((string) ($record['approved'] ?? 'Pending')); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </section>
</main>
<?php portal_footer(); ?>
