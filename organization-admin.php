<?php
require __DIR__ . '/portal-lib.php';
$admin = require_admin([YUVA_ROLE_ORGANIZATION_ADMIN]);

$organizationId = normalize_organization_id((string) $admin['organization_id']);
if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID) {
    http_response_code(403);
    exit('Access denied.');
}

$students = portal_students();
$records = read_json_file(portal_records_file());
$reports = safety_reports();
$memberships = organization_student_memberships_for_org($organizationId);
$query = strtolower(clean_text((string) ($_GET['q'] ?? '')));
$statusFilter = normalize_membership_status((string) ($_GET['membership_status'] ?? ''));
if (!isset($_GET['membership_status']) || $_GET['membership_status'] === '') {
    $statusFilter = '';
}
$groupFilter = clean_text((string) ($_GET['group'] ?? ''));

$filteredMemberships = array_filter($memberships, static function (array $membership) use ($students, $query, $statusFilter, $groupFilter): bool {
    if ($statusFilter !== '' && ($membership['status'] ?? '') !== $statusFilter) {
        return false;
    }
    if ($groupFilter !== '' && strcasecmp((string) ($membership['group'] ?? ''), $groupFilter) !== 0) {
        return false;
    }
    if ($query === '') {
        return true;
    }
    $student = $students[$membership['student_id'] ?? ''] ?? [];
    $haystack = strtolower(implode(' ', [
        (string) ($membership['student_id'] ?? ''),
        (string) ($membership['student_email'] ?? ''),
        student_display_name($student),
        (string) ($student['School'] ?? ''),
        (string) ($membership['group'] ?? ''),
        (string) ($membership['coach'] ?? ''),
        (string) ($membership['teacher'] ?? ''),
        (string) ($membership['moderator'] ?? ''),
    ]));
    return str_contains($haystack, $query);
});

$groups = [];
$activeCount = 0;
$invitedCount = 0;
foreach ($memberships as $membership) {
    $group = clean_text((string) ($membership['group'] ?? ''));
    if ($group !== '') {
        $groups[$group] = $group;
    }
    if (($membership['status'] ?? '') === 'Active') {
        $activeCount++;
    }
    if (($membership['status'] ?? '') === 'Invited') {
        $invitedCount++;
    }
}
ksort($groups);

$orgReports = array_filter(
    $reports,
    static fn ($report): bool => is_array($report)
        && organization_student_can_access($organizationId, (string) ($report['student_id'] ?? ''))
);

$statusMessages = [
    'student-invited' => 'Student invitation was recorded and sent when email delivery is available.',
    'student-linked' => 'Existing YUVA student was linked to this organization.',
    'membership-updated' => 'Membership assignment was updated.',
    'membership-archived' => 'Membership was archived. The global student account was not deleted.',
    'csv-imported' => 'CSV import completed.',
    'access-denied' => 'That student membership does not belong to your organization.',
    'student-error' => 'Student membership could not be saved. Check the required fields.',
    'csv-error' => 'CSV import could not be processed. Use columns like yuva_id, email, group, status, coach, teacher, moderator.',
];

portal_header('Organization Admin Dashboard');
?>
<main>
  <section class="band">
    <div class="section-head">
      <p class="eyebrow">Organization Administrator Dashboard</p>
      <h1><?php echo e($organizationId); ?></h1>
      <p>Manage organization memberships, assignments, and progress without deleting students' global YUVA identities.</p>
      <p><a class="button ghost" href="portal-logout.php">Log Out</a></p>
    </div>

    <?php if (isset($_GET['status'], $statusMessages[$_GET['status']])): ?>
      <div class="form-status <?php echo str_contains((string) $_GET['status'], 'error') || $_GET['status'] === 'access-denied' ? 'error' : 'success'; ?>">
        <?php echo e($statusMessages[$_GET['status']]); ?>
      </div>
    <?php endif; ?>

    <div class="dashboard-grid">
      <div class="metric-card">
        <span>Memberships</span>
        <strong><?php echo count($memberships); ?></strong>
      </div>
      <div class="metric-card">
        <span>Active</span>
        <strong><?php echo $activeCount; ?></strong>
      </div>
      <div class="metric-card">
        <span>Invited</span>
        <strong><?php echo $invitedCount; ?></strong>
      </div>
      <div class="metric-card">
        <span>Safety Reports</span>
        <strong><?php echo count($orgReports); ?></strong>
      </div>
    </div>

    <div class="dashboard-grid">
      <form class="form-card" method="post" action="organization-student-actions.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="invite_student">
        <h2>Invite New Student</h2>
        <p class="form-note">Creates an organization membership invitation only. The student still owns the global YUVA account after registration.</p>
        <div class="field-grid">
          <div class="field">
            <label>Student Email *</label>
            <input name="student_email" type="email" required>
          </div>
          <div class="field">
            <label>Group</label>
            <input name="group" type="text" placeholder="Example: Debate Cohort A">
          </div>
          <div class="field">
            <label>Coach</label>
            <input name="coach" type="text">
          </div>
          <div class="field">
            <label>Teacher</label>
            <input name="teacher" type="text">
          </div>
          <div class="field">
            <label>Moderator</label>
            <input name="moderator" type="text">
          </div>
          <div class="field">
            <label>Notes</label>
            <input name="notes" type="text">
          </div>
        </div>
        <button class="button primary" type="submit">Send Student Invitation</button>
      </form>

      <form class="form-card" method="post" action="organization-student-actions.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="link_existing">
        <h2>Add Existing YUVA Student</h2>
        <p class="form-note">Links an existing global YUVA ID to this organization after approval. No global records are overwritten.</p>
        <div class="field-grid">
          <div class="field">
            <label>YUVA ID *</label>
            <input name="student_id" type="text" required placeholder="YC2026001">
          </div>
          <div class="field">
            <label>Status</label>
            <select name="status">
              <?php foreach (organization_membership_statuses() as $status): ?>
                <option <?php echo $status === 'Active' ? 'selected' : ''; ?>><?php echo e($status); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Group</label>
            <input name="group" type="text">
          </div>
          <div class="field">
            <label>Coach</label>
            <input name="coach" type="text">
          </div>
          <div class="field">
            <label>Teacher</label>
            <input name="teacher" type="text">
          </div>
          <div class="field">
            <label>Moderator</label>
            <input name="moderator" type="text">
          </div>
        </div>
        <button class="button primary" type="submit">Link Student</button>
      </form>
    </div>

    <form class="form-card" method="post" action="organization-student-actions.php" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="import_csv">
      <h2>Import Students by CSV</h2>
      <p class="form-note">Accepted columns: yuva_id, student_id, email, student_email, group, status, coach, teacher, moderator, notes, send_invite. Imported rows become memberships for <?php echo e($organizationId); ?> only.</p>
      <div class="field-grid">
        <div class="field">
          <label>CSV File *</label>
          <input name="student_csv" type="file" accept=".csv,text/csv" required>
        </div>
      </div>
      <button class="button primary" type="submit">Import CSV</button>
    </form>

    <form class="form-card" method="get">
      <h2>Search and Filter Students</h2>
      <div class="field-grid">
        <div class="field">
          <label>Search</label>
          <input name="q" type="search" value="<?php echo e((string) ($_GET['q'] ?? '')); ?>" placeholder="Name, YUVA ID, email, school, group, coach">
        </div>
        <div class="field">
          <label>Status</label>
          <select name="membership_status">
            <option value="">All statuses</option>
            <?php foreach (organization_membership_statuses() as $status): ?>
              <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Group</label>
          <select name="group">
            <option value="">All groups</option>
            <?php foreach ($groups as $group): ?>
              <option value="<?php echo e($group); ?>" <?php echo $groupFilter === $group ? 'selected' : ''; ?>><?php echo e($group); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button class="button primary" type="submit">Apply Filters</button>
      <a class="button ghost" href="organization-admin.php">Clear</a>
    </form>

    <section class="form-card">
      <h2>Organization Student Memberships</h2>
      <?php if ($filteredMemberships === []): ?>
        <p>No organization memberships match the current filters.</p>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Membership</th>
                <th>Assignments</th>
                <th>Progress Summary</th>
                <th>Update</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filteredMemberships as $membershipKey => $membership): ?>
                <?php
                  $studentId = (string) ($membership['student_id'] ?? '');
                  $student = $studentId !== '' ? ($students[$studentId] ?? []) : [];
                  $record = $studentId !== '' ? ($records[$studentId] ?? student_record($studentId)) : [];
                  $summary = $studentId !== '' ? organization_student_progress_summary($studentId) : [];
                ?>
                <tr>
                  <td>
                    <strong><?php echo e($student !== [] ? student_display_name($student) : 'Invited Student'); ?></strong><br>
                    <?php echo $studentId !== '' ? e($studentId) : 'No YUVA ID yet'; ?><br>
                    <?php echo e((string) ($membership['student_email'] ?: ($student['Student Email'] ?? ''))); ?><br>
                    <?php echo e((string) ($student['School'] ?? '')); ?>
                  </td>
                  <td>
                    <strong><?php echo e((string) ($membership['status'] ?? '')); ?></strong><br>
                    Source: <?php echo e((string) ($membership['source'] ?? '')); ?><br>
                    Updated: <?php echo e(display_eastern_time((string) ($membership['updated_at'] ?? ''))); ?>
                    <form method="post" action="organization-student-actions.php" style="margin-top: .75rem;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="archive_membership">
                      <input type="hidden" name="membership_key" value="<?php echo e((string) $membershipKey); ?>">
                      <button class="button ghost" type="submit">Remove From Organization</button>
                    </form>
                  </td>
                  <td>
                    <strong>Group:</strong> <?php echo e((string) ($membership['group'] ?? '')); ?><br>
                    <strong>Coach:</strong> <?php echo e((string) ($membership['coach'] ?? '')); ?><br>
                    <strong>Teacher:</strong> <?php echo e((string) ($membership['teacher'] ?? '')); ?><br>
                    <strong>Moderator:</strong> <?php echo e((string) ($membership['moderator'] ?? '')); ?>
                  </td>
                  <td>
                    <?php if ($studentId === ''): ?>
                      <p>Progress appears after the student creates or links a YUVA account.</p>
                    <?php else: ?>
                      <strong>Status:</strong> <?php echo e((string) ($summary['approved'] ?? '')); ?><br>
                      <strong>Presentations:</strong> <?php echo e((string) ($summary['presentations'] ?? '0')); ?><br>
                      <strong>Certificates:</strong> <?php echo e((string) ($summary['certificates'] ?? '')); ?><br>
                      <strong>Volunteer Hours:</strong> <?php echo e((string) ($summary['volunteer_hours'] ?? '0')); ?><br>
                      <strong>Portfolio:</strong> <?php echo e((string) ($summary['portfolio'] ?? '')); ?><br>
                      <strong>Assigned Activity:</strong> <?php echo e((string) ($summary['assigned_activities'] ?? '')); ?><br>
                      <strong>Topic:</strong> <?php echo e((string) ($summary['topic'] ?? '')); ?><br>
                      <strong>Research:</strong> <?php echo e((string) ($summary['research'] ?? '')); ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="post" action="organization-student-actions.php">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="update_membership">
                      <input type="hidden" name="membership_key" value="<?php echo e((string) $membershipKey); ?>">
                      <label>Status</label>
                      <select name="status">
                        <?php foreach (organization_membership_statuses() as $status): ?>
                          <option <?php echo (($membership['status'] ?? '') === $status) ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <label>Group</label>
                      <input name="group" type="text" value="<?php echo e((string) ($membership['group'] ?? '')); ?>">
                      <label>Coach</label>
                      <input name="coach" type="text" value="<?php echo e((string) ($membership['coach'] ?? '')); ?>">
                      <label>Teacher</label>
                      <input name="teacher" type="text" value="<?php echo e((string) ($membership['teacher'] ?? '')); ?>">
                      <label>Moderator</label>
                      <input name="moderator" type="text" value="<?php echo e((string) ($membership['moderator'] ?? '')); ?>">
                      <label>Transfer To Organization</label>
                      <input name="transferred_to_organization_id" type="text" value="<?php echo e((string) ($membership['transferred_to_organization_id'] ?? '')); ?>">
                      <label>Notes</label>
                      <input name="notes" type="text" value="<?php echo e((string) ($membership['notes'] ?? '')); ?>">
                      <button class="button primary" type="submit">Save Membership</button>
                    </form>
                  </td>
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
