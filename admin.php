<?php
require __DIR__ . '/portal-lib.php';
require_admin();

$students = portal_students();
$selections = read_json_file(topic_selections_file());
$researchAll = read_json_file(research_file());
$records = read_json_file(portal_records_file());
$aiReviews = ai_reviews();
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

portal_header('Admin Dashboard');
?>
<main>
  <section class="band">
    <div class="section-head">
      <p class="eyebrow">Admin Dashboard</p>
      <h1>Yuva Club Records</h1>
      <p>Approve students, review topics and research, track attendance, add service hours, and prepare certificates.</p>
      <p><a class="button primary" href="admin-students.php">Signup Students</a> <a class="button ghost" href="portal-logout.php">Log Out</a></p>
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
    <?php elseif ($status === 'ai-applied'): ?>
      <div class="form-status success">AI Coach feedback and points were applied to the student profile.</div>
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
    <?php endif; ?>

    <form class="form-card" action="admin-password-actions.php" method="post">
      <h2>Admin Login Settings</h2>
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
          <input id="new_password" name="new_password" type="password" required minlength="8">
        </div>
        <div class="field">
          <label for="confirm_password">Confirm New Password *</label>
          <input id="confirm_password" name="confirm_password" type="password" required minlength="8">
        </div>
      </div>
      <button class="button primary" type="submit">Update Admin Login</button>
    </form>

    <form class="form-card" action="admin-hub-actions.php" method="post">
      <h2>Portal Hub Settings</h2>
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

    <div class="two-grid">
      <form class="form-card" action="admin-bulk-session-actions.php" method="post">
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

      <form class="form-card" action="admin-bulk-session-actions.php" method="post">
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

    <section class="form-card">
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

    <section class="form-card">
      <h2>Safety Reports</h2>
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

    <section class="form-card">
      <h2>AI Coach Reviews</h2>
      <p class="form-note">Run AI Coach after a student has selected a topic and submitted research. AI creates a draft score and feedback; admin must apply it before it becomes official.</p>
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
                  <?php else: ?>
                    No research submitted yet.
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (($aiReview['error'] ?? '') !== ''): ?>
                    <strong>Setup/Error:</strong> <?php echo e($aiReview['error']); ?>
                  <?php elseif ($aiDraft): ?>
                    <strong><?php echo e($aiReview['status'] ?? 'Draft'); ?></strong><br>
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
                    <?php if ($aiDraft): ?>
                      <form action="admin-ai-apply.php" method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                        <button class="button primary" type="submit">Apply AI Draft</button>
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
                <form id="admin-form-<?php echo e($studentId); ?>" action="admin-actions.php" method="post">
                  <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                  <div class="field">
                    <label>Topic Status</label>
                    <select name="topic_status">
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
                    <select name="research_status">
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
                    <form action="admin-ai-review.php" method="post">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                      <button class="button ghost" type="submit">Run AI Coach Review</button>
                    </form>
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
                          <form action="admin-ai-apply.php" method="post">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="student_id" value="<?php echo e($studentId); ?>">
                            <button class="button primary" type="submit">Apply AI Draft</button>
                          </form>
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
                    <select name="approved">
                      <?php foreach (['Pending', 'Approved', 'Waitlist', 'Inactive'] as $option): ?>
                        <option <?php echo (($record['approved'] ?? 'Pending') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Approved Leadership Rank</label>
                      <select name="current_rank">
                        <?php foreach (array_keys(rank_definitions()) as $option): ?>
                          <option <?php echo (approved_rank($record) === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field">
                      <label>Rank Status</label>
                      <select name="rank_status">
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
                    <input name="rank_recommendation" type="text" value="<?php echo e($record['rank_recommendation'] ?? ''); ?>" placeholder="AI or mentor recommendation">
                  </div>
                  <div class="field">
                    <label>Attendance</label>
                    <input name="attendance" type="number" min="0" value="<?php echo e($record['attendance'] ?? '0'); ?>">
                  </div>
                  <div class="field">
                    <label>Presentations</label>
                    <input name="presentations" type="number" min="0" value="<?php echo e($record['presentations'] ?? '0'); ?>">
                  </div>
                  <div class="field">
                    <label>Service Hours</label>
                    <input name="service_hours" type="number" min="0" step="0.25" value="<?php echo e($record['service_hours'] ?? '0'); ?>">
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Points</label>
                      <input name="points" type="number" min="0" value="<?php echo e((string) student_points($record)); ?>">
                    </div>
                    <div class="field">
                      <label>Tokens</label>
                      <input name="tokens" type="number" min="0" value="<?php echo e((string) student_tokens($record)); ?>">
                    </div>
                  </div>
                  <div class="field">
                    <label>Reward Status</label>
                    <select name="reward_status">
                      <?php foreach (['Not Yet', 'Bronze Reward', 'Silver Reward', 'Gold Reward', 'Gift Eligible', 'Gift Sent'] as $option): ?>
                        <option <?php echo (($record['reward_status'] ?? 'Not Yet') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field">
                    <label>Last Presentation Duration</label>
                    <input name="last_duration" type="text" value="<?php echo e($record['last_duration'] ?? ''); ?>" placeholder="5 minutes">
                  </div>
                  <div class="field">
                    <label>Score</label>
                    <input name="score" type="text" value="<?php echo e($record['score'] ?? ''); ?>" placeholder="Optional">
                  </div>
                  <div class="field">
                    <label>Teacher Feedback</label>
                    <textarea name="teacher_feedback"><?php echo e($record['teacher_feedback'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Challenge Stage</label>
                    <select name="challenge_stage">
                      <?php foreach (challenge_stages() as $option): ?>
                        <option <?php echo (challenge_stage($record) === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Challenge Month</label>
                      <input name="challenge_month" type="month" value="<?php echo e($record['challenge_month'] ?? date('Y-m')); ?>">
                    </div>
                    <div class="field">
                      <label>Region</label>
                      <input name="challenge_region" type="text" value="<?php echo e($record['challenge_region'] ?? ''); ?>" placeholder="Local, Southeast, Online, etc.">
                    </div>
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Finalist Status</label>
                      <select name="finalist_status">
                        <?php foreach (['Not Qualified', 'Eligible', 'Finalist', 'Champion'] as $option): ?>
                          <option <?php echo (($record['finalist_status'] ?? 'Not Qualified') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="field">
                      <label>Award Status</label>
                      <select name="award_status">
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
                        <input name="rubric_<?php echo e($rubricKey); ?>" type="number" min="1" max="10" value="<?php echo e($record['rubric_' . $rubricKey] ?? ''); ?>">
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="field">
                    <label>Judge Feedback</label>
                    <textarea name="judge_feedback" placeholder="Challenge notes, judging comments, next steps"><?php echo e($record['judge_feedback'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Mentor Feedback</label>
                    <textarea name="mentor_feedback" placeholder="Mentor notes, rank readiness, coaching suggestions"><?php echo e($record['mentor_feedback'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>AI Feedback Summary</label>
                    <textarea name="ai_feedback_summary" placeholder="Encouraging summary after presentation review"><?php echo e($record['ai_feedback_summary'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Communication Skills</label>
                    <textarea name="communication_skills" placeholder="Pace, clarity, confidence, organization"><?php echo e($record['communication_skills'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Leadership Milestones</label>
                    <textarea name="leadership_milestones" placeholder="First presentation, mentor role, service project, etc."><?php echo e($record['leadership_milestones'] ?? ''); ?></textarea>
                  </div>
                  <div class="field">
                    <label>Student Zoom Session Title</label>
                    <input name="student_session_title" type="text" value="<?php echo e($record['student_session_title'] ?? ''); ?>" placeholder="Presentation Session">
                  </div>
                  <div class="field-grid">
                    <div class="field">
                      <label>Student Session Date</label>
                      <input name="student_session_date" type="date" value="<?php echo e($record['student_session_date'] ?? ''); ?>">
                    </div>
                    <div class="field">
                      <label>Start Time</label>
                      <input name="student_session_start" type="time" value="<?php echo e($record['student_session_start'] ?? ''); ?>">
                    </div>
                    <div class="field">
                      <label>End Time</label>
                      <input name="student_session_end" type="time" value="<?php echo e($record['student_session_end'] ?? ''); ?>">
                    </div>
                  </div>
                  <div class="field">
                    <label>Student Zoom Status</label>
                    <select name="student_session_status">
                      <?php foreach (['Closed', 'Open', 'Starting Soon', 'Completed'] as $option): ?>
                        <option <?php echo (($record['student_session_status'] ?? 'Closed') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field">
                    <label>Student Zoom Link</label>
                    <input name="student_zoom_url" type="url" value="<?php echo e($record['student_zoom_url'] ?? ''); ?>" placeholder="https://zoom.us/j/...">
                  </div>
                  <div class="field">
                    <label>Student Zoom Meeting ID</label>
                    <input name="student_zoom_meeting_id" type="text" value="<?php echo e($record['student_zoom_meeting_id'] ?? ''); ?>" placeholder="Zoom meeting ID">
                  </div>
                  <div class="field">
                    <label>Student Zoom Password</label>
                    <input name="student_zoom_password" type="text" value="<?php echo e($record['student_zoom_password'] ?? ''); ?>" placeholder="Meeting passcode">
                  </div>
                  <div class="field">
                    <label>Certificate Status</label>
                    <select name="certificate_status">
                      <?php foreach (['Not Ready', 'Ready', 'Issued'] as $option): ?>
                        <option <?php echo (($record['certificate_status'] ?? 'Not Ready') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="field">
                    <label>Admin Notes</label>
                    <textarea name="admin_notes"><?php echo e($record['admin_notes'] ?? ''); ?></textarea>
                  </div>
              </td>
              <td>
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
</main>
<?php portal_footer(); ?>
