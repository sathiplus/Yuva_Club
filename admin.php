<?php
require __DIR__ . '/portal-lib.php';
require_once __DIR__ . '/backend/repositories.php';
require_admin();

$sqlApprovalEnabled = sql_approval_enabled();
$sqlPendingRegistrations = [];
$sqlRegistrationListAvailable = $sqlApprovalEnabled;
if ($sqlApprovalEnabled) {
    try {
        $sqlPendingRegistrations = pending_sql_registrations();
    } catch (Throwable $error) {
        $sqlRegistrationListAvailable = false;
        error_log(
            'YUVA SQL registration list failed'
            . ' correlation=' . bin2hex(random_bytes(12))
            . ' exception_type=' . get_class($error)
        );
    }
}

$students = portal_students();
$selections = read_json_file(topic_selections_file());
$researchAll = read_json_file(research_file());
$records = read_json_file(portal_records_file());
$aiReviews = ai_reviews();
$mediaAnalysisEnabled = ai_mentor_feature_enabled('media_analysis_enabled');
$presentationMediaAll = read_json_file(presentation_media_file());
$deliveryReviews = [];
if ($mediaAnalysisEnabled && database_settings_present() && db_is_sqlsrv()) {
    foreach (array_keys($students) as $deliveryStudentId) {
        try { $deliveryReviews[$deliveryStudentId] = delivery_review_repository()->findLatest((string)$deliveryStudentId); } catch (Throwable) { $deliveryReviews[$deliveryStudentId] = []; }
    }
}
$hub = hub_settings();
$schoolSession = group_session($hub, 'junior');
$collegeSession = group_session($hub, 'senior');
$reports = safety_reports();
$status = $_GET['status'] ?? '';
$scheduledMeetings = [];

foreach ($records as $recordStudentId => $record) {
    $recordStudentId = normalize_yuva_id((string) $recordStudentId);
    if (!isset($students[$recordStudentId])) {
        continue;
    }

    $meetingTitle = $record['student_session_title'] ?? '';
    $meetingDate = $record['student_session_date'] ?? '';
    $meetingStart = $record['student_session_start'] ?? '';
    $meetingEnd = $record['student_session_end'] ?? '';
    $meetingStatus = $record['student_session_status'] ?? 'Closed';
    $meetingZoomUrl = $record['student_zoom_url'] ?? '';
    $meetingZoomMeetingId = $record['student_zoom_meeting_id'] ?? '';
    $meetingZoomPassword = $record['student_zoom_password'] ?? '';
    $studentSessionDefaults = group_session($hub, student_program_group($students[$recordStudentId]));
    if ($meetingZoomUrl === '' || str_starts_with($meetingZoomUrl, 'https://scheduler.zoom.us/')) {
        $meetingZoomUrl = $studentSessionDefaults['zoom_url'] ?? '';
    }
    if ($meetingZoomMeetingId === '') {
        $meetingZoomMeetingId = $studentSessionDefaults['zoom_meeting_id'] ?? '';
    }
    if ($meetingZoomPassword === '') {
        $meetingZoomPassword = $studentSessionDefaults['zoom_password'] ?? '';
    }

    if ($meetingTitle === '' && $meetingDate === '' && $meetingStart === '' && $meetingEnd === '' && $meetingZoomUrl === '') {
        continue;
    }

    $meetingKey = md5(json_encode([$meetingTitle, $meetingDate, $meetingStart, $meetingEnd, $meetingStatus, $meetingZoomUrl, $meetingZoomMeetingId, $meetingZoomPassword]));
    if (!isset($scheduledMeetings[$meetingKey])) {
        $scheduledMeetings[$meetingKey] = [
            'title' => $meetingTitle,
            'date' => $meetingDate,
            'start' => $meetingStart,
            'end' => $meetingEnd,
            'status' => $meetingStatus,
            'zoom_url' => $meetingZoomUrl,
            'zoom_meeting_id' => $meetingZoomMeetingId,
            'zoom_password' => $meetingZoomPassword,
            'participants' => [],
        ];
    }

    $scheduledMeetings[$meetingKey]['participants'][] = [
        'id' => $recordStudentId,
        'name' => student_display_name($students[$recordStudentId]),
        'group' => student_program_group($students[$recordStudentId]) === 'junior' ? 'School Yuva' : 'College Yuva',
    ];
}

$pendingApprovalCount = $sqlRegistrationListAvailable
    ? count($sqlPendingRegistrations)
    : 0;
$openSafetyReportCount = count(array_filter(
    $reports,
    static fn (array $report): bool =>
        strtolower((string) ($report['status'] ?? 'Open')) !== 'closed'
));
$issuedCertificateCount = count(array_filter(
    $records,
    static fn (array $record): bool =>
        ($record['certificate_status'] ?? 'Not Ready') === 'Issued'
));

portal_header('Master Admin', false, ['assets/master-admin.css?v=1']);
?>
<a class="master-skip-link" href="#master-main">Skip to control center</a>
<div class="master-shell">
  <aside class="master-rail" aria-label="Master Admin navigation">
    <a class="master-brand" href="#overview">
      <img src="assets/logo.png" alt="">
      <span><strong>YUVA Club</strong><small>Master Admin</small></span>
    </a>
    <nav class="master-nav" aria-label="Master Admin sections">
      <a href="#overview"><span aria-hidden="true">O</span> Overview</a>
      <a href="#organizations"><span aria-hidden="true">OR</span> Organizations</a>
      <a href="#organization-admins"><span aria-hidden="true">OA</span> Organization Admins</a>
      <a href="#students"><span aria-hidden="true">ST</span> Students</a>
      <a href="#parents"><span aria-hidden="true">PA</span> Parents</a>
      <a href="#sql-registrations"><span aria-hidden="true">AP</span> Approvals</a>
      <a href="#certificates"><span aria-hidden="true">CE</span> Certificates</a>
      <a href="#reports"><span aria-hidden="true">!</span> Reports</a>
      <a href="#settings"><span aria-hidden="true">SE</span> Settings</a>
      <a href="#system-health"><span aria-hidden="true">SH</span> System Health</a>
    </nav>
    <a class="master-logout" href="portal-logout.php">Log out</a>
  </aside>
  <main class="master-main" id="master-main">
    <header class="master-mobile-header">
      <a class="master-mobile-brand" href="#overview"><img src="assets/yuva-symbol.png" alt=""><span>Master Admin</span></a>
      <a class="master-mobile-action" href="portal-logout.php">Log out</a>
    </header>
    <section class="master-hero" id="overview">
      <div>
        <p class="master-kicker">System-wide control center</p>
        <h1>Lead the whole YUVA Club platform with clarity.</h1>
        <p>Review access, student operations, program delivery, safety, and platform readiness from one accountable workspace.</p>
      </div>
      <div class="master-hero-actions">
        <a class="button primary" href="admin-students.php">Manage signup students</a>
        <a class="button ghost" href="#sql-registrations">Review approvals</a>
      </div>
    </section>

    <section class="master-stat-grid" aria-label="Platform overview">
      <article><span>Students</span><strong><?php echo count($students); ?></strong><small>Current portal records</small></article>
      <article><span>Pending approvals</span><strong><?php echo $pendingApprovalCount; ?></strong><small><?php echo $sqlApprovalEnabled ? 'SQL approval queue' : 'SQL approval is disabled'; ?></small></article>
      <article><span>Scheduled sessions</span><strong><?php echo count($scheduledMeetings); ?></strong><small>Across both programs</small></article>
      <article><span>Open safety reports</span><strong><?php echo $openSafetyReportCount; ?></strong><small>Require responsible review</small></article>
    </section>

    <section class="master-module-grid" aria-label="Master Admin areas">
      <article id="organizations">
        <span class="master-module-mark organizations" aria-hidden="true">OR</span>
        <div><p class="master-kicker">Organizations</p><h2>Organization foundation</h2><p>The approved foundation is present. Organization management remains intentionally unavailable until its future implementation phase.</p></div>
        <span class="master-state neutral">Foundation only</span>
      </article>
      <article id="organization-admins">
        <span class="master-module-mark admins" aria-hidden="true">OA</span>
        <div><p class="master-kicker">Organization Admins</p><h2>Role foundation</h2><p>No organization administrators are managed from this Version 2.0 control center.</p></div>
        <span class="master-state neutral">Not configured</span>
      </article>
      <article id="parents">
        <span class="master-module-mark parents" aria-hidden="true">PA</span>
        <div><p class="master-kicker">Parents</p><h2>Parent identities</h2><p>Parent access continues through the existing linked-student workflow and preserved authentication contract.</p></div>
        <span class="master-state ready">Operational</span>
      </article>
      <article id="system-health">
        <span class="master-module-mark health" aria-hidden="true">SH</span>
        <div><p class="master-kicker">System Health</p><h2>Current operating state</h2><p>Filesystem portal records are available. SQL registration approval is <?php echo $sqlApprovalEnabled ? 'enabled' : 'disabled'; ?>.</p></div>
        <span class="master-state <?php echo $sqlRegistrationListAvailable ? 'ready' : 'attention'; ?>"><?php echo $sqlRegistrationListAvailable ? 'Available' : 'Attention'; ?></span>
      </article>
    </section>

    <section class="master-workspace">
      <div class="master-section-heading">
        <div><p class="master-kicker">Operations</p><h2>Platform management</h2></div>
        <p>Existing administrative workflows, reorganized without changing their behavior.</p>
      </div>

    <?php if ($status === 'saved'): ?>
      <div class="form-status success">Student record saved.</div>
    <?php elseif ($status === 'hub-saved'): ?>
      <div class="form-status success">Portal hub settings saved.</div>
    <?php elseif ($status === 'bulk-saved'): ?>
      <div class="form-status success">Bulk student Zoom sessions saved.</div>
    <?php elseif ($status === 'bulk-empty'): ?>
      <div class="form-status error">No students were selected for bulk assignment.</div>
    <?php elseif ($status === 'meeting-updated'): ?>
      <div class="form-status success">Scheduled meeting participants updated.</div>
    <?php elseif ($status === 'meeting-empty'): ?>
      <div class="form-status error">Select at least one student to remove from a scheduled meeting.</div>
    <?php elseif ($status === 'password-saved'): ?>
      <div class="form-status success">Admin login updated.</div>
    <?php elseif ($status === 'password-error'): ?>
      <div class="form-status error">Admin login was not updated. Check current login, new email, and matching password fields.</div>
    <?php elseif ($status === 'ai-reviewed'): ?>
      <div class="form-status success">AI Coach draft review created. Please review and apply it before it becomes official.</div>
    <?php elseif ($status === 'ai-draft-saved'): ?>
      <div class="form-status success">AI Mentor draft edits were saved.</div>
    <?php elseif ($status === 'ai-edit-invalid'): ?>
      <div class="form-status error">Draft edits were not valid. Check every required field.</div>
    <?php elseif ($status === 'ai-edit-conflict'): ?>
      <div class="form-status error">The draft changed in another session. Reload before editing again.</div>
    <?php elseif ($status === 'ai-applied'): ?>
      <div class="form-status success">AI Mentor feedback and its approved token award were applied.</div>
    <?php elseif ($status === 'ai-already-applied'): ?>
      <div class="form-status success">This AI Coach review was already applied. No additional tokens were awarded.</div>
    <?php elseif ($status === 'ai-stale'): ?>
      <div class="form-status error">This AI Coach review is out of date. Generate a new review before applying feedback.</div>
    <?php elseif ($status === 'ai-error'): ?>
      <div class="form-status error">AI Coach could not run. Check that OPENAI_API_KEY is configured on the server.</div>
    <?php elseif ($status === 'ai-missing'): ?>
      <div class="form-status error">AI Coach needs a student with a selected topic and submitted research.</div>
    <?php elseif ($status === 'security-error'): ?>
      <div class="form-status error">This form expired. Please try again.</div>
    <?php elseif ($status === 'sql-registration-approved'): ?>
      <div class="form-status success">Registration approved successfully.</div>
    <?php elseif ($status === 'sql-registration-unavailable'): ?>
      <div class="form-status error">Registration approval is unavailable.</div>
    <?php elseif ($status === 'sql-registration-invalid'): ?>
      <div class="form-status error">Invalid request.</div>
    <?php elseif ($status === 'sql-registration-error'): ?>
      <div class="form-status error">Registration could not be approved.</div>
    <?php endif; ?>

    <section class="form-card master-panel" id="sql-registrations">
      <div class="master-panel-heading"><div><p class="master-kicker">Approvals</p><h2>Pending Azure SQL registrations</h2></div><span class="master-count"><?php echo $pendingApprovalCount; ?></span></div>
      <?php if (!$sqlApprovalEnabled || !$sqlRegistrationListAvailable): ?>
        <p class="form-note">Registration approval is unavailable.</p>
      <?php elseif ($sqlPendingRegistrations === []): ?>
        <p class="form-note">No pending Azure SQL registrations.</p>
      <?php else: ?>
        <div class="portal-table-wrap">
          <table class="portal-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Submitted</th>
                <th>Program</th>
                <th>School</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($sqlPendingRegistrations as $registration): ?>
                <?php
                  $registrationName = trim(
                      (string) ($registration['student_first_name'] ?? '')
                      . ' '
                      . (string) ($registration['student_last_name'] ?? '')
                  );
                ?>
                <tr>
                  <td>
                    <strong><?php echo e($registrationName); ?></strong>
                    <?php if (($registration['preferred_name'] ?? '') !== ''): ?>
                      <br><span>Preferred: <?php echo e((string) $registration['preferred_name']); ?></span>
                    <?php endif; ?>
                    <?php if (($registration['grade'] ?? '') !== ''): ?>
                      <br><span>Grade: <?php echo e((string) $registration['grade']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo e((string) ($registration['submitted_at'] ?? '')); ?></td>
                  <td><?php echo e((string) ($registration['program_name'] ?? 'Unassigned')); ?></td>
                  <td><?php echo e((string) ($registration['school'] ?? '')); ?></td>
                  <td><?php echo e((string) ($registration['status'] ?? '')); ?></td>
                  <td>
                    <form action="admin-registration-approve.php" method="post">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="registration_id" value="<?php echo e((string) ($registration['id'] ?? '')); ?>">
                      <button class="button primary" type="submit">Approve Registration</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <form class="form-card master-panel" id="settings" action="admin-password-actions.php" method="post">
      <p class="master-kicker">Settings</p>
      <h2>Master Admin login</h2>
      <div class="field-grid">
        <div class="field">
          <label for="current_email">Current Admin Email *</label>
          <input id="current_email" name="current_email" type="email" required value="<?php echo e($_SESSION['admin_email'] ?? 'admin@karmabro.com'); ?>">
        </div>
        <div class="field">
          <label for="current_password">Current Password *</label>
          <input id="current_password" name="current_password" type="password" required>
        </div>
        <div class="field">
          <label for="new_email">New Admin Email *</label>
          <input id="new_email" name="new_email" type="email" required value="<?php echo e($_SESSION['admin_email'] ?? 'admin@karmabro.com'); ?>">
        </div>
        <div class="field">
          <label for="new_password">New Password *</label>
          <input id="new_password" name="new_password" type="password" required minlength="12">
        </div>
        <div class="field">
          <label for="confirm_password">Confirm New Password *</label>
          <input id="confirm_password" name="confirm_password" type="password" required minlength="12">
        </div>
      </div>
      <button class="button primary" type="submit">Update Admin Login</button>
    </form>

    <form class="form-card master-panel" action="admin-hub-actions.php" method="post">
      <p class="master-kicker">Program delivery</p>
      <h2>Portal hub settings</h2>
      <h2>School Yuva Session (Ages 13-17)</h2>
      <div class="field-grid">
        <div class="field">
          <label for="junior_session_title">Session Title</label>
          <input id="junior_session_title" name="junior_session_title" type="text" value="<?php echo e($hub['junior_session_title']); ?>">
        </div>
        <div class="field">
          <label for="junior_session_date">Session Date</label>
          <input id="junior_session_date" name="junior_session_date" type="date" value="<?php echo e($hub['junior_session_date']); ?>">
        </div>
        <div class="field">
          <label for="junior_session_start">Start Time</label>
          <input id="junior_session_start" name="junior_session_start" type="time" value="<?php echo e($hub['junior_session_start']); ?>">
        </div>
        <div class="field">
          <label for="junior_session_end">End Time</label>
          <input id="junior_session_end" name="junior_session_end" type="time" value="<?php echo e($hub['junior_session_end']); ?>">
        </div>
        <div class="field">
          <label for="junior_session_status">Session Status</label>
          <select id="junior_session_status" name="junior_session_status">
            <?php foreach (['Closed', 'Open', 'Starting Soon', 'Completed'] as $option): ?>
              <option <?php echo ($hub['junior_session_status'] === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="junior_zoom_url">Zoom Link</label>
          <input id="junior_zoom_url" name="junior_zoom_url" type="url" value="<?php echo e($schoolSession['zoom_url']); ?>" placeholder="https://zoom.us/j/...">
        </div>
        <div class="field">
          <label for="junior_zoom_meeting_id">Zoom Meeting ID</label>
          <input id="junior_zoom_meeting_id" name="junior_zoom_meeting_id" type="text" value="<?php echo e($schoolSession['zoom_meeting_id']); ?>" placeholder="Zoom meeting ID">
        </div>
        <div class="field">
          <label for="junior_zoom_password">Zoom Password</label>
          <input id="junior_zoom_password" name="junior_zoom_password" type="text" value="<?php echo e($schoolSession['zoom_password']); ?>" placeholder="Meeting passcode">
        </div>
      </div>
      <div class="field">
        <label for="junior_scheduler_embed">School Yuva Zoom Scheduler Embed Code</label>
        <textarea id="junior_scheduler_embed" name="junior_scheduler_embed" placeholder="<iframe src=&quot;https://scheduler.zoom.us/...&quot; ...></iframe>"><?php echo e($hub['junior_scheduler_embed'] ?? ''); ?></textarea>
      </div>
      <h2>College Yuva Session (Ages 18-21)</h2>
      <div class="field-grid">
        <div class="field">
          <label for="senior_session_title">Session Title</label>
          <input id="senior_session_title" name="senior_session_title" type="text" value="<?php echo e($hub['senior_session_title']); ?>">
        </div>
        <div class="field">
          <label for="senior_session_date">Session Date</label>
          <input id="senior_session_date" name="senior_session_date" type="date" value="<?php echo e($hub['senior_session_date']); ?>">
        </div>
        <div class="field">
          <label for="senior_session_start">Start Time</label>
          <input id="senior_session_start" name="senior_session_start" type="time" value="<?php echo e($hub['senior_session_start']); ?>">
        </div>
        <div class="field">
          <label for="senior_session_end">End Time</label>
          <input id="senior_session_end" name="senior_session_end" type="time" value="<?php echo e($hub['senior_session_end']); ?>">
        </div>
        <div class="field">
          <label for="senior_session_status">Session Status</label>
          <select id="senior_session_status" name="senior_session_status">
            <?php foreach (['Closed', 'Open', 'Starting Soon', 'Completed'] as $option): ?>
              <option <?php echo ($hub['senior_session_status'] === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="senior_zoom_url">Zoom Link</label>
          <input id="senior_zoom_url" name="senior_zoom_url" type="url" value="<?php echo e($collegeSession['zoom_url']); ?>" placeholder="https://zoom.us/j/...">
        </div>
        <div class="field">
          <label for="senior_zoom_meeting_id">Zoom Meeting ID</label>
          <input id="senior_zoom_meeting_id" name="senior_zoom_meeting_id" type="text" value="<?php echo e($collegeSession['zoom_meeting_id']); ?>" placeholder="Zoom meeting ID">
        </div>
        <div class="field">
          <label for="senior_zoom_password">Zoom Password</label>
          <input id="senior_zoom_password" name="senior_zoom_password" type="text" value="<?php echo e($collegeSession['zoom_password']); ?>" placeholder="Meeting passcode">
        </div>
      </div>
      <div class="field">
        <label for="senior_scheduler_embed">College Yuva Zoom Scheduler Embed Code</label>
        <textarea id="senior_scheduler_embed" name="senior_scheduler_embed" placeholder="<iframe src=&quot;https://scheduler.zoom.us/...&quot; ...></iframe>"><?php echo e($hub['senior_scheduler_embed'] ?? ''); ?></textarea>
      </div>
      <div class="field">
        <label for="announcements">Announcements</label>
        <textarea id="announcements" name="announcements" placeholder="One announcement per line"><?php echo e($hub['announcements']); ?></textarea>
      </div>
      <div class="field">
        <label for="recordings">Session Recordings</label>
        <textarea id="recordings" name="recordings" placeholder="Title|https://recording-link"><?php echo e($hub['recordings']); ?></textarea>
      </div>
      <div class="field">
        <label for="resources">Resources</label>
        <textarea id="resources" name="resources" placeholder="Title|url"><?php echo e($hub['resources']); ?></textarea>
      </div>
      <button class="button primary" type="submit">Save Hub Settings</button>
    </form>

    <div class="two-grid master-schedule-grid">
      <form class="form-card master-panel" action="admin-bulk-session-actions.php" method="post">
        <h2>Bulk Assign School Yuva Zoom Slot</h2>
        <div class="field-grid">
          <div class="field">
            <label>Session Title</label>
            <input name="student_session_title" type="text" value="<?php echo e($hub['junior_session_title'] ?? 'School Yuva Session'); ?>">
          </div>
          <div class="field">
            <label>Session Date</label>
            <input name="student_session_date" type="date" value="<?php echo e($hub['junior_session_date'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Start Time</label>
            <input name="student_session_start" type="time" value="<?php echo e($hub['junior_session_start'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>End Time</label>
            <input name="student_session_end" type="time" value="<?php echo e($hub['junior_session_end'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Status</label>
            <select name="student_session_status">
              <?php foreach (['Closed', 'Open', 'Starting Soon', 'Completed'] as $option): ?>
                <option <?php echo (($hub['junior_session_status'] ?? 'Closed') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Zoom Link</label>
            <input name="student_zoom_url" type="url" value="<?php echo e($schoolSession['zoom_url']); ?>" placeholder="https://zoom.us/j/...">
          </div>
          <div class="field">
            <label>Zoom Meeting ID</label>
            <input name="student_zoom_meeting_id" type="text" value="<?php echo e($schoolSession['zoom_meeting_id']); ?>" placeholder="Zoom meeting ID">
          </div>
          <div class="field">
            <label>Zoom Password</label>
            <input name="student_zoom_password" type="text" value="<?php echo e($schoolSession['zoom_password']); ?>" placeholder="Meeting passcode">
          </div>
        </div>
        <div class="field">
          <label>Select School Yuva Students</label>
          <div class="choice-grid compact-choice-grid">
            <?php foreach ($students as $studentId => $student): ?>
              <?php if (student_program_group($student) !== 'junior') { continue; } ?>
              <label><input type="checkbox" name="selected_students[]" value="<?php echo e($studentId); ?>"> <?php echo e(student_display_name($student)); ?> <span><?php echo e($studentId); ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="button primary" type="submit">Assign School Yuva Slot</button>
      </form>

      <form class="form-card master-panel" action="admin-bulk-session-actions.php" method="post">
        <h2>Bulk Assign College Yuva Zoom Slot</h2>
        <div class="field-grid">
          <div class="field">
            <label>Session Title</label>
            <input name="student_session_title" type="text" value="<?php echo e($hub['senior_session_title'] ?? 'College Yuva Session'); ?>">
          </div>
          <div class="field">
            <label>Session Date</label>
            <input name="student_session_date" type="date" value="<?php echo e($hub['senior_session_date'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Start Time</label>
            <input name="student_session_start" type="time" value="<?php echo e($hub['senior_session_start'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>End Time</label>
            <input name="student_session_end" type="time" value="<?php echo e($hub['senior_session_end'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Status</label>
            <select name="student_session_status">
              <?php foreach (['Closed', 'Open', 'Starting Soon', 'Completed'] as $option): ?>
                <option <?php echo (($hub['senior_session_status'] ?? 'Closed') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Zoom Link</label>
            <input name="student_zoom_url" type="url" value="<?php echo e($collegeSession['zoom_url']); ?>" placeholder="https://zoom.us/j/...">
          </div>
          <div class="field">
            <label>Zoom Meeting ID</label>
            <input name="student_zoom_meeting_id" type="text" value="<?php echo e($collegeSession['zoom_meeting_id']); ?>" placeholder="Zoom meeting ID">
          </div>
          <div class="field">
            <label>Zoom Password</label>
            <input name="student_zoom_password" type="text" value="<?php echo e($collegeSession['zoom_password']); ?>" placeholder="Meeting passcode">
          </div>
        </div>
        <div class="field">
          <label>Select College Yuva Students</label>
          <div class="choice-grid compact-choice-grid">
            <?php foreach ($students as $studentId => $student): ?>
              <?php if (student_program_group($student) !== 'senior') { continue; } ?>
              <label><input type="checkbox" name="selected_students[]" value="<?php echo e($studentId); ?>"> <?php echo e(student_display_name($student)); ?> <span><?php echo e($studentId); ?></span></label>
            <?php endforeach; ?>
          </div>
        </div>
        <button class="button primary" type="submit">Assign College Yuva Slot</button>
      </form>
    </div>

    <section class="form-card master-panel">
      <h2>Scheduled Meetings</h2>
      <p class="form-note">Students appear here after you assign them with the School Yuva or College Yuva bulk assignment forms. Remove selected students from a meeting without deleting their registration.</p>
      <?php if ($scheduledMeetings === []): ?>
        <p>No scheduled student meetings yet.</p>
      <?php else: ?>
        <div class="meeting-list">
          <?php foreach ($scheduledMeetings as $meeting): ?>
            <form class="meeting-card" action="admin-meeting-actions.php" method="post">
              <h3><?php echo e($meeting['title'] ?: 'Yuva Club Session'); ?></h3>
              <p><strong>Date:</strong> <?php echo e($meeting['date'] ?: 'Not set'); ?></p>
              <p><strong>Time:</strong> <?php echo e($meeting['start'] ?: '--:--'); ?> - <?php echo e($meeting['end'] ?: '--:--'); ?></p>
              <p><strong>Status:</strong> <?php echo e($meeting['status']); ?></p>
              <?php if (($meeting['zoom_url'] ?? '') !== ''): ?>
                <p><strong>Zoom Link:</strong> <a href="<?php echo e($meeting['zoom_url']); ?>" target="_blank" rel="noopener">Open Zoom</a></p>
              <?php endif; ?>
              <?php if (($meeting['zoom_meeting_id'] ?? '') !== ''): ?>
                <p><strong>Zoom Meeting ID:</strong> <?php echo e($meeting['zoom_meeting_id']); ?></p>
              <?php endif; ?>
              <?php if (($meeting['zoom_password'] ?? '') !== ''): ?>
                <p><strong>Zoom Password:</strong> <?php echo e($meeting['zoom_password']); ?></p>
              <?php endif; ?>
              <p><strong>Participants:</strong> <?php echo count($meeting['participants']); ?></p>
              <div class="choice-grid compact-choice-grid">
                <?php foreach ($meeting['participants'] as $participant): ?>
                  <label><input type="checkbox" name="selected_students[]" value="<?php echo e($participant['id']); ?>"> <?php echo e($participant['name']); ?> <span><?php echo e($participant['id']); ?> | <?php echo e($participant['group']); ?></span></label>
                <?php endforeach; ?>
              </div>
              <button class="button ghost" type="submit">Remove Selected from Meeting</button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="form-card master-panel" id="reports">
      <p class="master-kicker">Reports</p>
      <h2>Safety reports</h2>
      <p class="form-note">Reports submitted from the student app dashboard. Follow up with the parent or student outside the app as needed.</p>
      <?php if ($reports === []): ?>
        <p>No student safety reports yet.</p>
      <?php else: ?>
        <div class="portal-table-wrap">
          <table class="portal-table compact-table">
            <thead>
              <tr>
                <th>Submitted</th>
                <th>Student</th>
                <th>Type</th>
                <th>Message</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($reports) as $report): ?>
                <tr>
                  <td><?php echo e($report['submitted_at'] ?? ''); ?></td>
                  <td>
                    <strong><?php echo e($report['student_name'] ?? ''); ?></strong><br>
                    <?php echo e($report['student_id'] ?? ''); ?><br>
                    <?php echo e($report['program_group'] ?? ''); ?><br>
                    <?php echo e($report['parent_email'] ?? ''); ?>
                  </td>
                  <td><?php echo e($report['type'] ?? ''); ?></td>
                  <td><?php echo e($report['message'] ?? ''); ?></td>
                  <td><?php echo e($report['status'] ?? 'Open'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="form-card master-panel" id="ai-mentor-reviews">
      <p class="master-kicker">Review workflow</p>
      <h2>AI Mentor reviews</h2>
      <p class="form-note">Run AI Mentor after a student has selected a topic and submitted text research. AI creates an advisory Draft; an administrator must review, edit, and apply it before the student can see it.</p>
      <div class="portal-table-wrap">
        <table class="portal-table compact-table">
          <thead>
            <tr>
              <th>Student</th>
              <th>Topic</th>
              <th>Research</th>
              <th>AI Draft</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $studentId => $student): ?>
              <?php
                $selection = $selections[$studentId] ?? [];
                $research = $researchAll[$studentId] ?? [];
                $aiReview = $aiReviews[$studentId] ?? [];
                $aiDraft = $aiReview['review'] ?? [];
              ?>
              <tr>
                <td>
                  <strong><?php echo e(student_display_name($student)); ?></strong><br>
                  <?php echo e($studentId); ?>
                </td>
                <td>
                  <strong><?php echo e($selection['topic_title'] ?? 'No topic selected'); ?></strong><br>
                  <?php echo e($selection['topic_category'] ?? ''); ?>
                </td>
                <td>
                  <?php if ($research): ?>
                    Submitted <?php echo e($research['updated_at'] ?? ''); ?><br>
                    <?php echo e($research['status'] ?? 'Pending Admin Review'); ?>
                    <?php if (!empty($research['file_original'])): ?>
                      <br><strong>Document:</strong> <?php echo e((string) $research['file_original']); ?>
                      <?php if (!empty($research['file_size'])): ?>
                        (<?php echo e(number_format(((int) $research['file_size']) / 1048576, 2)); ?> MiB)
                      <?php endif; ?>
                    <?php endif; ?>
                  <?php else: ?>
                    No research submitted yet.
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (($aiReview['error'] ?? '') !== ''): ?>
                    <strong>Setup/Error:</strong> <?php echo e($aiReview['error']); ?>
                  <?php elseif ($aiDraft): ?>
                    <strong><?php echo e($aiReview['status'] ?? 'Draft'); ?></strong><br>
                    <?php if (($aiReview['document_analysis_status'] ?? 'NotApplicable') !== 'NotApplicable'): ?>
                      Document: <?php echo e((string) ($aiReview['source_file_original_name'] ?? 'Uploaded document')); ?>
                      — <?php echo e((string) $aiReview['document_analysis_status']); ?><br>
                    <?php endif; ?>
                    Points: <?php echo e((string) ($aiDraft['total_points'] ?? '0')); ?><br>
                    Tokens: <?php echo e((string) ($aiDraft['suggested_tokens'] ?? '0')); ?><br>
                    <?php echo e($aiDraft['summary'] ?? ''); ?>
                  <?php else: ?>
                    No AI draft yet.
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($selection && $research): ?>
                    <form action="admin-ai-review.php" method="post">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                      <button class="button ghost" type="submit">Run AI Coach Review</button>
                    </form>
                    <?php if ($aiDraft && ($aiReview['status'] ?? '') === 'Draft'): ?>
                      <form action="admin-ai-save-draft.php" method="post" class="form-card">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                        <input type="hidden" name="version" value="<?php echo e((string) ($aiReview['version'] ?? '')); ?>">
                        <label>Summary<textarea name="summary" required maxlength="2000"><?php echo e((string) ($aiDraft['summary'] ?? '')); ?></textarea></label>
                        <label>Strengths, one per line<textarea name="strengths" required maxlength="3600"><?php echo e(implode("\n", $aiDraft['strengths'] ?? [])); ?></textarea></label>
                        <label>Improvements, one per line<textarea name="improvements" required maxlength="3600"><?php echo e(implode("\n", $aiDraft['improvements'] ?? [])); ?></textarea></label>
                        <label>Communication feedback<textarea name="communication_skills" required maxlength="2000"><?php echo e((string) ($aiDraft['communication_skills'] ?? '')); ?></textarea></label>
                        <label>Leadership feedback<textarea name="leadership_milestones" required maxlength="2000"><?php echo e((string) ($aiDraft['leadership_milestones'] ?? '')); ?></textarea></label>
                        <label>Recommended next step<textarea name="recommended_next_step" required maxlength="1200"><?php echo e((string) ($aiDraft['recommended_next_step'] ?? '')); ?></textarea></label>
                        <label>Suggested tokens<input type="number" name="suggested_tokens" min="0" max="4" required value="<?php echo e((string) ($aiDraft['suggested_tokens'] ?? '0')); ?>"></label>
                        <p class="form-note">Category scores are read-only advisory evidence and remain separate from the official presentation rubric.</p>
                        <button class="button ghost" type="submit">Save Draft</button>
                      </form>
                      <form action="admin-ai-apply.php" method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                        <button class="button primary" type="submit">Apply Review</button>
                      </form>
                    <?php endif; ?>
                  <?php else: ?>
                    <p class="form-note">Student needs topic selection and research first.</p>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($students === []): ?>
              <tr><td colspan="5">No registered students found yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <?php if ($mediaAnalysisEnabled): ?>
    <section class="form-card master-panel" id="ai-mentor-delivery-reviews">
      <p class="master-kicker">Presentation coaching</p><h2>AI Mentor delivery reviews</h2>
      <p class="form-note">Recordings are sensitive student content. AI creates a Draft; only an Applied, administrator-reviewed version becomes student-visible. Delivery scores remain advisory and separate from the official rubric.</p>
      <div class="portal-table-wrap"><table class="portal-table compact-table"><thead><tr><th>Student</th><th>Recording</th><th>Evidence</th><th>Action</th></tr></thead><tbody>
      <?php foreach ($students as $deliveryStudentId => $deliveryStudent): $media=$presentationMediaAll[$deliveryStudentId]??[];$mediaActive=is_array($media)&&($media['retention_status']??'Active')==='Active';$delivery=$deliveryReviews[$deliveryStudentId]??[];$coaching=$delivery['review']??[]; ?>
      <tr><td><strong><?php echo e(student_display_name($deliveryStudent)); ?></strong><br><?php echo e((string)$deliveryStudentId); ?></td>
      <td><?php echo $mediaActive?e((string)($media['original_filename']??'Recording')):'No active recording.'; ?><?php if($mediaActive): ?><br><?php echo e((string)($media['mime_type']??'')); ?> · <?php echo e(number_format(((int)($media['size_bytes']??0))/1048576,2)); ?> MiB<?php endif; ?></td>
      <td><?php if($delivery): ?><strong><?php echo e((string)($delivery['status']??'')); ?></strong><br><?php echo e((string)($delivery['transcription_model']??'')); ?> · <?php echo e((string)($delivery['media_duration_seconds']??'')); ?> seconds<?php if($coaching): ?><br>Delivery score: <?php echo e((string)($coaching['overall_delivery_score']??'')); ?> · Tokens: <?php echo e((string)($coaching['suggested_tokens']??0)); ?><?php endif; ?><?php else: ?>No delivery review yet.<?php endif; ?></td>
      <td><?php if($mediaActive): ?><form action="admin-delivery-review.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="student_id" value="<?php echo e((string)$deliveryStudentId); ?>"><button class="button ghost" type="submit">Run Delivery Review</button></form><?php endif; ?>
      <?php if($coaching&&($delivery['status']??'')==='Draft'): ?><form action="admin-delivery-save-draft.php" method="post" class="form-card"><?php echo csrf_field(); ?><input type="hidden" name="student_id" value="<?php echo e((string)$deliveryStudentId); ?>"><input type="hidden" name="version" value="<?php echo e((string)($delivery['version']??'')); ?>">
      <?php foreach(['summary','pacing_feedback','pause_feedback','clarity_feedback','filler_word_feedback','visual_feedback','recommended_next_step','admin_notes'] as $field): ?><label><?php echo e(ucwords(str_replace('_',' ',$field))); ?><textarea name="<?php echo e($field); ?>" required maxlength="2400"><?php echo e((string)($coaching[$field]??'')); ?></textarea></label><?php endforeach; ?>
      <?php foreach(['strengths','improvements','pronunciation_practice','emphasis_opportunities'] as $field): ?><label><?php echo e(ucwords(str_replace('_',' ',$field))); ?>, one per line<textarea name="<?php echo e($field); ?>" maxlength="6000"><?php echo e(implode("\n",$coaching[$field]??[])); ?></textarea></label><?php endforeach; ?>
      <label>Time-coded coaching JSON<textarea name="timecoded_coaching_json" required maxlength="12000"><?php echo e(json_encode($coaching['timecoded_coaching']??[],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)?:'[]'); ?></textarea></label>
      <label>Suggested tokens<input type="number" name="suggested_tokens" min="0" max="4" value="<?php echo e((string)($coaching['suggested_tokens']??0)); ?>"></label><button class="button ghost" type="submit">Save Delivery Draft</button></form>
      <form action="admin-delivery-apply.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="student_id" value="<?php echo e((string)$deliveryStudentId); ?>"><button class="button primary" type="submit">Apply Delivery Review</button></form><?php endif; ?></td></tr>
      <?php endforeach; ?></tbody></table></div>
    </section>
    <?php endif; ?>

    <section class="master-panel master-student-panel" id="students">
      <div class="master-panel-heading" id="certificates"><div><p class="master-kicker">Students / Certificates</p><h2>Student records and recognition</h2></div><span class="master-count"><?php echo $issuedCertificateCount; ?> issued</span></div>
      <p class="form-note">Manage existing student records, progress evidence, approvals, sessions, and certificate status.</p>
    <div class="portal-table-wrap">
      <table class="portal-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Topic</th>
            <th>Research</th>
            <th>Tracking</th>
            <th>Save</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $studentId => $student): ?>
            <?php
              $record = $records[$studentId] ?? student_record($studentId);
              $selection = $selections[$studentId] ?? [];
              $research = $researchAll[$studentId] ?? [];
              $aiReview = $aiReviews[$studentId] ?? [];
              $aiDraft = $aiReview['review'] ?? [];
              $studentUpdateFormId = 'admin-form-' . $studentId;
            ?>
            <tr>
              <td>
                <strong><?php echo e(student_display_name($student)); ?></strong><br>
                <span><?php echo e($studentId); ?></span><br>
                <span><?php echo e($student['Grade'] ?? ''); ?></span><br>
                <span><?php echo e($student['Parent Email'] ?? ''); ?></span>
                <p><a class="button ghost" href="admin-student-edit.php?id=<?php echo e($studentId); ?>">Edit Signup</a></p>
              </td>
              <td>
                  <div class="field">
                    <label>Topic Status</label>
                    <select name="topic_status" form="<?php echo e($studentUpdateFormId); ?>">
                      <?php foreach (['Pending Admin Review', 'Approved', 'Needs Changes'] as $option): ?>
                        <option <?php echo (($selection['status'] ?? 'Pending Admin Review') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <p><strong><?php echo e($selection['topic_title'] ?? 'No topic selected'); ?></strong></p>
                  <p><?php echo e($selection['topic_category'] ?? ''); ?></p>
                  <p><?php echo e($selection['presentation_date'] ?? ''); ?> <?php echo e($selection['presentation_time'] ?? ''); ?></p>
              </td>
              <td>
                  <div class="field">
                    <label>Research Status</label>
                    <select name="research_status" form="<?php echo e($studentUpdateFormId); ?>">
                      <?php foreach (['Pending Admin Review', 'Approved', 'Needs Changes'] as $option): ?>
                        <option <?php echo (($research['status'] ?? 'Pending Admin Review') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <?php if ($research): ?>
                    <p><strong>Submitted:</strong> <?php echo e($research['updated_at'] ?? ''); ?></p>
                    <details>
                      <summary>View Notes</summary>
                      <p><strong>Notes:</strong> <?php echo e($research['research_notes'] ?? ''); ?></p>
                      <p><strong>Sources:</strong> <?php echo e($research['sources_used'] ?? ''); ?></p>
                      <p><strong>Outline:</strong> <?php echo e($research['presentation_outline'] ?? ''); ?></p>
                      <p><strong>Questions:</strong> <?php echo e($research['prepared_questions'] ?? ''); ?></p>
                    </details>
                    <?php if (!empty($research['file_original'])): ?>
                      <p><a href="portal-download.php?id=<?php echo e($studentId); ?>"><?php echo e($research['file_original']); ?></a></p>
                    <?php endif; ?>
                    <?php if ($aiReview): ?>
                      <div class="ai-review-box">
                        <p><strong>AI Status:</strong> <?php echo e($aiReview['status'] ?? 'Draft'); ?></p>
                        <p><strong>Reviewed:</strong> <?php echo e($aiReview['reviewed_at'] ?? ''); ?></p>
                        <?php if (($aiReview['error'] ?? '') !== ''): ?>
                          <p><strong>Setup/Error:</strong> <?php echo e($aiReview['error']); ?></p>
                        <?php endif; ?>
                        <?php if ($aiDraft): ?>
                          <p><strong>Draft Points:</strong> <?php echo e((string) ($aiDraft['total_points'] ?? '0')); ?></p>
                          <p><strong>Suggested Tokens:</strong> <?php echo e((string) ($aiDraft['suggested_tokens'] ?? '0')); ?></p>
                          <p><strong>Summary:</strong> <?php echo e($aiDraft['summary'] ?? ''); ?></p>
                          <?php if (!empty($aiDraft['strengths']) && is_array($aiDraft['strengths'])): ?>
                            <p><strong>Strengths:</strong> <?php echo e(implode(', ', $aiDraft['strengths'])); ?></p>
                          <?php endif; ?>
                          <?php if (!empty($aiDraft['improvements']) && is_array($aiDraft['improvements'])): ?>
                            <p><strong>Improvements:</strong> <?php echo e(implode(', ', $aiDraft['improvements'])); ?></p>
                          <?php endif; ?>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <p>No research submitted.</p>
                  <?php endif; ?>
              </td>
              <td>
                  <div class="field">
                    <label>Approval</label>
                    <select name="approved" form="<?php echo e($studentUpdateFormId); ?>">
                      <?php foreach (['Pending', 'Approved', 'Waitlist', 'Inactive'] as $option): ?>
                        <option <?php echo (($record['approved'] ?? 'Pending') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Approved Leadership Rank</label>
                      <select name="current_rank" form="<?php echo e($studentUpdateFormId); ?>">
                        <?php foreach (array_keys(rank_definitions()) as $option): ?>
                          <option <?php echo (approved_rank($record) === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field">
                      <label>Rank Status</label>
                      <select name="rank_status" form="<?php echo e($studentUpdateFormId); ?>">
                        <?php foreach (['Approved', 'Eligible for Review', 'Needs More Evidence', 'Pending Mentor Review'] as $option): ?>
                          <option <?php echo (($record['rank_status'] ?? 'Approved') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="field">
                    <label>System Rank Eligibility</label>
                    <input type="text" value="<?php echo e(rank_eligibility($record)); ?>" readonly>
                  </div>
                  <div class="field">
                    <label>Rank Recommendation</label>
                    <input name="rank_recommendation" type="text" value="<?php echo e($record['rank_recommendation'] ?? ''); ?>" placeholder="AI or mentor recommendation" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Attendance</label>
                    <input name="attendance" type="number" min="0" value="<?php echo e($record['attendance'] ?? '0'); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Presentations</label>
                    <input name="presentations" type="number" min="0" value="<?php echo e($record['presentations'] ?? '0'); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Service Hours</label>
                    <input name="service_hours" type="number" min="0" step="0.25" value="<?php echo e($record['service_hours'] ?? '0'); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Points</label>
                      <input name="points" type="number" min="0" value="<?php echo e((string) student_points($record)); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                    </div>
                    <div class="field">
                      <label>Tokens</label>
                      <input name="tokens" type="number" min="0" value="<?php echo e((string) student_tokens($record)); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                    </div>
                  </div>
                  <div class="field">
                    <label>Reward Status</label>
                    <select name="reward_status" form="<?php echo e($studentUpdateFormId); ?>">
                      <?php foreach (['Not Yet', 'Bronze Reward', 'Silver Reward', 'Gold Reward', 'Gift Eligible', 'Gift Sent'] as $option): ?>
                        <option <?php echo (($record['reward_status'] ?? 'Not Yet') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field">
                    <label>Last Presentation Duration</label>
                    <input name="last_duration" type="text" value="<?php echo e($record['last_duration'] ?? ''); ?>" placeholder="5 minutes" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Score</label>
                    <input name="score" type="text" value="<?php echo e($record['score'] ?? ''); ?>" placeholder="Optional" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Teacher Feedback</label>
                    <textarea name="teacher_feedback" form="<?php echo e($studentUpdateFormId); ?>"><?php echo e($record['teacher_feedback'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Challenge Stage</label>
                    <select name="challenge_stage" form="<?php echo e($studentUpdateFormId); ?>">
                      <?php foreach (challenge_stages() as $option): ?>
                        <option <?php echo (challenge_stage($record) === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Challenge Month</label>
                      <input name="challenge_month" type="month" value="<?php echo e($record['challenge_month'] ?? date('Y-m')); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                    </div>
                    <div class="field">
                      <label>Region</label>
                      <input name="challenge_region" type="text" value="<?php echo e($record['challenge_region'] ?? ''); ?>" placeholder="Local, Southeast, Online, etc." form="<?php echo e($studentUpdateFormId); ?>">
                    </div>
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Finalist Status</label>
                      <select name="finalist_status" form="<?php echo e($studentUpdateFormId); ?>">
                        <?php foreach (['Not Qualified', 'Eligible', 'Finalist', 'Champion'] as $option): ?>
                          <option <?php echo (($record['finalist_status'] ?? 'Not Qualified') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field">
                      <label>Award Status</label>
                      <select name="award_status" form="<?php echo e($studentUpdateFormId); ?>">
                        <?php foreach (['None', 'Badge Earned', 'Certificate Ready', 'Trophy Eligible', 'Award Issued'] as $option): ?>
                          <option <?php echo (($record['award_status'] ?? 'None') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="field">
                    <label>Challenge Rubric Score</label>
                    <input type="text" value="<?php echo e((string) rubric_score($record)); ?> / 100" readonly>
                  </div>
                  <div class="field-grid">
                    <?php foreach (rubric_categories() as $rubricKey => $rubricLabel): ?>
                      <div class="field">
                        <label><?php echo e($rubricLabel); ?></label>
                        <input name="rubric_<?php echo e($rubricKey); ?>" type="number" min="1" max="10" value="<?php echo e($record['rubric_' . $rubricKey] ?? ''); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="field">
                    <label>Judge Feedback</label>
                    <textarea name="judge_feedback" placeholder="Challenge notes, judging comments, next steps" form="<?php echo e($studentUpdateFormId); ?>"><?php echo e($record['judge_feedback'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Mentor Feedback</label>
                    <textarea name="mentor_feedback" placeholder="Mentor notes, rank readiness, coaching suggestions" form="<?php echo e($studentUpdateFormId); ?>"><?php echo e($record['mentor_feedback'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>AI Feedback Summary</label>
                    <textarea name="ai_feedback_summary" placeholder="Encouraging summary after presentation review" form="<?php echo e($studentUpdateFormId); ?>"><?php echo e($record['ai_feedback_summary'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Communication Skills</label>
                    <textarea name="communication_skills" placeholder="Pace, clarity, confidence, organization" form="<?php echo e($studentUpdateFormId); ?>"><?php echo e($record['communication_skills'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Leadership Milestones</label>
                    <textarea name="leadership_milestones" placeholder="First presentation, mentor role, service project, etc." form="<?php echo e($studentUpdateFormId); ?>"><?php echo e($record['leadership_milestones'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Student Zoom Session Title</label>
                    <input name="student_session_title" type="text" value="<?php echo e($record['student_session_title'] ?? ''); ?>" placeholder="Presentation Session" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Student Session Date</label>
                      <input name="student_session_date" type="date" value="<?php echo e($record['student_session_date'] ?? ''); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                    </div>
                    <div class="field">
                      <label>Start Time</label>
                      <input name="student_session_start" type="time" value="<?php echo e($record['student_session_start'] ?? ''); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                    </div>
                    <div class="field">
                      <label>End Time</label>
                      <input name="student_session_end" type="time" value="<?php echo e($record['student_session_end'] ?? ''); ?>" form="<?php echo e($studentUpdateFormId); ?>">
                    </div>
                  </div>
                  <div class="field">
                    <label>Student Zoom Status</label>
                    <select name="student_session_status" form="<?php echo e($studentUpdateFormId); ?>">
                      <?php foreach (['Closed', 'Open', 'Starting Soon', 'Completed'] as $option): ?>
                        <option <?php echo (($record['student_session_status'] ?? 'Closed') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field">
                    <label>Student Zoom Link</label>
                    <input name="student_zoom_url" type="url" value="<?php echo e($record['student_zoom_url'] ?? ''); ?>" placeholder="https://zoom.us/j/..." form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Student Zoom Meeting ID</label>
                    <input name="student_zoom_meeting_id" type="text" value="<?php echo e($record['student_zoom_meeting_id'] ?? ''); ?>" placeholder="Zoom meeting ID" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Student Zoom Password</label>
                    <input name="student_zoom_password" type="text" value="<?php echo e($record['student_zoom_password'] ?? ''); ?>" placeholder="Meeting passcode" form="<?php echo e($studentUpdateFormId); ?>">
                  </div>
                  <div class="field">
                    <label>Certificate Status</label>
                    <select name="certificate_status" form="<?php echo e($studentUpdateFormId); ?>">
                      <?php foreach (['Not Ready', 'Ready', 'Issued'] as $option): ?>
                        <option <?php echo (($record['certificate_status'] ?? 'Not Ready') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field">
                    <label>Admin Notes</label>
                    <textarea name="admin_notes" form="<?php echo e($studentUpdateFormId); ?>"><?php echo e($record['admin_notes'] ?? ''); ?></textarea>
                  </div>
              </td>
              <td>
                <form id="<?php echo e($studentUpdateFormId); ?>" action="admin-actions.php" method="post">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                  <button class="button primary" type="submit">Save</button>
                  <p><a class="button ghost" href="certificate.php?id=<?php echo e($studentId); ?>" target="_blank">Certificate</a></p>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if ($students === []): ?>
            <tr><td colspan="5">No registered students found yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    </section>
    </section>
  </main>
  <nav class="master-bottom-nav" aria-label="Master Admin mobile navigation">
    <a href="#overview"><span aria-hidden="true">O</span>Overview</a>
    <a href="#students"><span aria-hidden="true">ST</span>Students</a>
    <a href="#sql-registrations"><span aria-hidden="true">AP</span>Approvals</a>
    <a href="#reports"><span aria-hidden="true">!</span>Reports</a>
    <a href="#settings"><span aria-hidden="true">SE</span>Settings</a>
  </nav>
</div>
<?php portal_footer(); ?>
