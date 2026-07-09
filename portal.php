<?php
require __DIR__ . '/portal-lib.php';
$student = require_student();
$studentId = $student['Yuva Club ID'];
$name = student_display_name($student);
$selections = read_json_file(topic_selections_file());
$selection = $selections[$studentId] ?? null;
$researchAll = read_json_file(research_file());
$research = $researchAll[$studentId] ?? null;
$record = student_record($studentId);
$topics = yuva_topic_categories();
$status = $_GET['status'] ?? '';
$hub = hub_settings();
$studentGroup = student_program_group($student);
$session = group_session($hub, $studentGroup);
$schedulerSrc = scheduler_embed_src($session['scheduler_embed'] ?? '');
$studentSessionTitle = $record['student_session_title'] ?? '';
$studentSessionDate = $record['student_session_date'] ?? '';
$studentSessionStart = $record['student_session_start'] ?? '';
$studentSessionEnd = $record['student_session_end'] ?? '';
$studentSessionStatus = $record['student_session_status'] ?? 'Closed';
$savedStudentZoomUrl = trim((string) ($record['student_zoom_url'] ?? ''));
$studentZoomUrl = str_starts_with($savedStudentZoomUrl, 'https://scheduler.zoom.us/') ? '' : $savedStudentZoomUrl;
$studentZoomMeetingId = ($record['student_zoom_meeting_id'] ?? '') !== '' ? ($record['student_zoom_meeting_id'] ?? '') : ($session['zoom_meeting_id'] ?? '');
$studentZoomPassword = ($record['student_zoom_password'] ?? '') !== '' ? ($record['student_zoom_password'] ?? '') : ($session['zoom_password'] ?? '');
$effectiveZoomUrl = $studentZoomUrl !== '' ? $studentZoomUrl : ($session['zoom_url'] ?? '');
$hasStudentZoom = $studentSessionTitle !== '' || $studentSessionDate !== '' || $effectiveZoomUrl !== '';
$effectiveBrowserZoomUrl = zoom_browser_join_url($effectiveZoomUrl);
$schedulerPageUrl = scheduler_page_url($schedulerSrc);
$level = leadership_level($record);
$eligibleRank = rank_eligibility($record);
$membershipGroupLabel = membership_group_label($student);
$badges = earned_badges($record);
$points = student_points($record);
$tokens = student_tokens($record);
$rewardLevel = reward_level($record);
$challengeStage = challenge_stage($record);
$rubricScore = rubric_score($record);
$rubricCompleted = rubric_completed_count($record);
$nextRank = next_rank_name($level);

portal_header('Student Dashboard');
?>
<main>
  <section class="band">
    <div class="section-head">
      <p class="eyebrow">Student Dashboard</p>
      <h1>Welcome, <?php echo e($name); ?></h1>
      <p>The Yuva Club website is your official hub for sessions, presentations, research, leadership hours, certificates, recordings, and announcements.</p>
      <p><a class="button ghost" href="portal-logout.php">Log Out</a></p>
    </div>

    <?php if ($status === 'topic-saved'): ?><div class="form-status success">Topic selection saved.</div><?php endif; ?>
    <?php if ($status === 'topic-taken'): ?><div class="form-status error">This topic is already selected by another student. Please choose a different topic.</div><?php endif; ?>
    <?php if ($status === 'research-saved'): ?><div class="form-status success">Research submission saved.</div><?php endif; ?>
    <?php if ($status === 'upload-error'): ?><div class="form-status error">Research saved, but the upload file type was not accepted.</div><?php endif; ?>
    <?php if ($status === 'report-sent'): ?><div class="form-status success">Your report was sent to the Yuva Club admin team.</div><?php endif; ?>

    <div class="portal-stat-grid">
      <div class="feature"><strong>Yuva Club ID</strong><p><?php echo e($studentId); ?></p></div>
      <div class="feature"><strong>Membership Group</strong><p><?php echo e($membershipGroupLabel); ?></p></div>
      <div class="feature"><strong>Approval</strong><p><?php echo e($record['approved'] ?? 'Pending'); ?></p></div>
      <div class="feature"><strong>Leadership Rank</strong><p><?php echo e($level); ?></p></div>
      <div class="feature"><strong>Eligible Rank</strong><p><?php echo e($eligibleRank); ?></p></div>
      <div class="feature"><strong>Presentations</strong><p><?php echo e($record['presentations'] ?? '0'); ?></p></div>
      <div class="feature"><strong>Attendance</strong><p><?php echo e($record['attendance'] ?? '0'); ?> sessions</p></div>
      <div class="feature"><strong>Points</strong><p><?php echo e((string) $points); ?></p></div>
      <div class="feature"><strong>Tokens</strong><p><?php echo e((string) $tokens); ?></p></div>
      <div class="feature"><strong>Reward Level</strong><p><?php echo e($rewardLevel); ?></p></div>
      <div class="feature"><strong>Challenge Stage</strong><p><?php echo e($challengeStage); ?></p></div>
      <div class="feature"><strong>Rubric Score</strong><p><?php echo e((string) $rubricScore); ?> / 100</p></div>
      <div class="feature"><strong>Service Hours</strong><p><?php echo e($record['service_hours'] ?? '0'); ?> hours</p></div>
      <div class="feature"><strong>Certificate</strong><p><?php echo e($record['certificate_status'] ?? 'Not Ready'); ?></p></div>
    </div>
  </section>

  <section class="band alt">
    <div class="section-head">
      <p class="eyebrow">Leadership Challenge</p>
      <h2>The Global Youth Speaking & Leadership Challenge</h2>
      <p>Track your challenge stage, presentation score, badges, certificates, and next leadership milestone.</p>
    </div>
    <div class="portal-stat-grid">
      <div class="feature"><strong>Program</strong><p><?php echo e($membershipGroupLabel); ?></p></div>
      <div class="feature"><strong>Current Level</strong><p><?php echo e($level); ?></p></div>
      <div class="feature"><strong>Next Milestone</strong><p><?php echo e($nextRank === $level ? 'Mentor recognition and continued service' : $nextRank); ?></p></div>
      <div class="feature"><strong>Challenge Stage</strong><p><?php echo e($challengeStage); ?></p></div>
      <div class="feature"><strong>Challenge Month</strong><p><?php echo e($record['challenge_month'] ?? date('Y-m')); ?></p></div>
      <div class="feature"><strong>Finalist Status</strong><p><?php echo e($record['finalist_status'] ?? 'Not Qualified'); ?></p></div>
    </div>
    <div class="challenge-path">
      <?php foreach (challenge_stages() as $stage): ?>
        <span class="<?php echo $stage === $challengeStage ? 'active' : ''; ?>"><?php echo e($stage); ?></span>
      <?php endforeach; ?>
    </div>
    <p><a class="button ghost" href="leaderboard.php">View Challenge Leaderboard</a></p>
  </section>

  <section class="band">
    <div class="two-grid">
      <div class="form-card">
        <h2>Presentation Rubric</h2>
        <p><strong>Total:</strong> <?php echo e((string) $rubricScore); ?> / 100</p>
        <p><strong>Categories Scored:</strong> <?php echo e((string) $rubricCompleted); ?> / <?php echo e((string) count(rubric_categories())); ?></p>
        <div class="rubric-grid">
          <?php foreach (rubric_categories() as $rubricKey => $rubricLabel): ?>
            <div><strong><?php echo e($rubricLabel); ?></strong><span><?php echo e((string) ($record['rubric_' . $rubricKey] ?? 'Not scored')); ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-card">
        <h2>Judge Feedback</h2>
        <p><?php echo e($record['judge_feedback'] ?? 'Challenge feedback will appear after a mentor or judge reviews your presentation.'); ?></p>
        <p><strong>Award Status:</strong> <?php echo e($record['award_status'] ?? 'None'); ?></p>
      </div>
    </div>
  </section>

  <section class="band">
    <div class="two-grid">
      <div class="form-card hub-card">
        <h2>My Zoom Session</h2>
        <?php if ($hasStudentZoom): ?>
          <p><strong><?php echo e($studentSessionTitle ?: 'Yuva Club Session'); ?></strong></p>
          <p><?php echo e($studentSessionDate ?: 'Date to be announced'); ?></p>
          <p><?php echo e($studentSessionStart ?: '--:--'); ?> - <?php echo e($studentSessionEnd ?: '--:--'); ?></p>
          <p><strong>Status:</strong> <?php echo e($studentSessionStatus); ?></p>
          <?php if ($studentZoomMeetingId !== ''): ?>
            <p><strong>Zoom Meeting ID:</strong> <?php echo e($studentZoomMeetingId); ?></p>
          <?php endif; ?>
          <?php if ($studentZoomPassword !== ''): ?>
            <p><strong>Zoom Password:</strong> <?php echo e($studentZoomPassword); ?></p>
          <?php endif; ?>
          <?php if ($effectiveZoomUrl !== ''): ?>
            <p class="form-note">Use the button below to join the Zoom meeting. The embedded scheduler stays visible below.</p>
            <div class="button-row">
              <a class="button primary" href="<?php echo e($effectiveZoomUrl); ?>" target="_blank" rel="noopener">Join Zoom</a>
              <?php if ($effectiveBrowserZoomUrl !== ''): ?>
                <a class="button ghost" href="<?php echo e($effectiveBrowserZoomUrl); ?>" target="_blank" rel="noopener">Join from Browser</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($schedulerSrc !== ''): ?>
            <p><a class="button ghost" href="<?php echo e($schedulerPageUrl ?: $schedulerSrc); ?>" target="_blank" rel="noopener">Open Zoom Scheduler in New Tab</a></p>
            <div class="zoom-scheduler-frame compact-scheduler-frame">
              <iframe src="<?php echo e($schedulerSrc); ?>" frameborder="0" width="750" height="560" title="Yuva Club Zoom Scheduler"></iframe>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <p>Your personal Zoom date and time have not been assigned yet. The Zoom scheduler is available below.</p>
          <?php if ($schedulerSrc !== ''): ?>
            <p><a class="button ghost" href="<?php echo e($schedulerPageUrl ?: $schedulerSrc); ?>" target="_blank" rel="noopener">Open Zoom Scheduler in New Tab</a></p>
            <div class="zoom-scheduler-frame compact-scheduler-frame">
              <iframe src="<?php echo e($schedulerSrc); ?>" frameborder="0" width="750" height="560" title="Yuva Club Zoom Scheduler"></iframe>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="form-card hub-card">
        <h2>Today's Session</h2>
        <p><strong><?php echo e($session['title']); ?></strong></p>
        <p><?php echo e($session['group_label']); ?></p>
        <p><?php echo e($session['date'] ?: 'Date to be announced'); ?></p>
        <p><?php echo e($session['start'] ?: '--:--'); ?> - <?php echo e($session['end'] ?: '--:--'); ?></p>
        <p><strong>Status:</strong> <?php echo e($session['status']); ?></p>
        <?php if (($session['zoom_meeting_id'] ?? '') !== ''): ?>
          <p><strong>Zoom Meeting ID:</strong> <?php echo e($session['zoom_meeting_id']); ?></p>
        <?php endif; ?>
        <?php if (($session['zoom_password'] ?? '') !== ''): ?>
          <p><strong>Zoom Password:</strong> <?php echo e($session['zoom_password']); ?></p>
        <?php endif; ?>
        <?php if (($session['zoom_url'] ?? '') !== ''): ?>
          <a class="button primary" href="<?php echo e($session['zoom_url']); ?>" target="_blank" rel="noopener">Join Zoom</a>
          <?php $groupBrowserZoomUrl = zoom_browser_join_url($session['zoom_url'] ?? ''); ?>
          <?php if ($groupBrowserZoomUrl !== ''): ?>
            <a class="button ghost" href="<?php echo e($groupBrowserZoomUrl); ?>" target="_blank" rel="noopener">Join from Browser</a>
          <?php endif; ?>
        <?php else: ?>
          <p class="form-note">Admin has not posted the shared Zoom link yet.</p>
        <?php endif; ?>
      </div>

      <div class="form-card hub-card">
        <h2>Upcoming Presentation</h2>
        <?php if ($selection): ?>
          <p><strong><?php echo e($selection['topic_title']); ?></strong></p>
          <p><?php echo e($selection['presentation_date']); ?> at <?php echo e($selection['presentation_time']); ?></p>
          <p><strong>Status:</strong> <?php echo e($selection['status'] ?? 'Pending Admin Review'); ?></p>
          <p><strong>Research:</strong> <?php echo e($research['status'] ?? 'Not Submitted'); ?></p>
        <?php else: ?>
          <p>No presentation topic selected yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="band">
    <div class="form-card">
      <h2>Zoom Scheduler</h2>
      <p>Select a meeting time for Yuva Club using the embedded scheduler below. This is the embeddable Zoom Scheduler page.</p>
      <?php if ($schedulerSrc !== ''): ?>
        <p><a class="button ghost" href="<?php echo e($schedulerPageUrl ?: $schedulerSrc); ?>" target="_blank" rel="noopener">Open Scheduler in New Tab</a></p>
        <div class="zoom-scheduler-frame">
          <iframe src="<?php echo e($schedulerSrc); ?>" frameborder="0" width="750" height="560" title="Yuva Club Zoom Scheduler"></iframe>
        </div>
      <?php else: ?>
        <p class="form-note">The Zoom Scheduler will appear here after admin adds the embed code for your group.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="band">
    <div class="section-head">
      <h2>Student Profile</h2>
      <p>Your profile grows as you attend, present, research, receive feedback, and serve.</p>
    </div>
    <div class="portal-profile-grid">
      <div class="form-card">
        <h2>Progress</h2>
        <p><strong>Membership Group:</strong> <?php echo e($membershipGroupLabel); ?></p>
        <p><strong>Approved Leadership Rank:</strong> <?php echo e($level); ?></p>
        <p><strong>Rank Eligibility:</strong> <?php echo e($eligibleRank); ?></p>
        <p><strong>Rank Status:</strong> <?php echo e($record['rank_status'] ?? 'Approved'); ?></p>
        <p><strong>Rank Recommendation:</strong> <?php echo e($record['rank_recommendation'] ?? 'Not reviewed yet.'); ?></p>
        <p><strong>Points:</strong> <?php echo e((string) $points); ?></p>
        <p><strong>Tokens:</strong> <?php echo e((string) $tokens); ?></p>
        <p><strong>Reward Level:</strong> <?php echo e($rewardLevel); ?></p>
        <p><strong>Presentations:</strong> <?php echo e($record['presentations'] ?? '0'); ?></p>
        <p><strong>Hours Earned:</strong> <?php echo e($record['service_hours'] ?? '0'); ?></p>
        <p><strong>AI Feedback Summary:</strong> <?php echo e($record['ai_feedback_summary'] ?? 'No AI feedback yet.'); ?></p>
        <p><strong>Communication Skills:</strong> <?php echo e($record['communication_skills'] ?? 'Not recorded yet.'); ?></p>
        <p><strong>Leadership Milestones:</strong> <?php echo e($record['leadership_milestones'] ?? 'Not recorded yet.'); ?></p>
        <p><strong>Mentor Feedback:</strong> <?php echo e($record['mentor_feedback'] ?? 'No mentor feedback yet.'); ?></p>
        <p><strong>Teacher Feedback:</strong> <?php echo e($record['teacher_feedback'] ?? 'No feedback yet.'); ?></p>
      </div>
      <div class="form-card">
        <h2>Badges</h2>
        <?php if ($badges): ?>
          <div class="badge-list">
            <?php foreach ($badges as $badge): ?><span><?php echo e($badge); ?></span><?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>Badges will appear as you participate.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="band">
    <div class="portal-module-grid">
      <?php foreach (rank_definitions() as $rankName => $rankInfo): ?>
        <div class="feature portal-module"><strong><?php echo e($rankName); ?></strong><p><?php echo e($rankInfo['meaning']); ?>. <?php echo e($rankInfo['requirements']); ?></p></div>
      <?php endforeach; ?>
      <a class="feature portal-module" href="#topic-selection"><strong>Choose Topic</strong><p>Select your category, title, date, and time.</p></a>
      <a class="feature portal-module" href="#research-submission"><strong>Submit Research</strong><p>Send notes, sources, outline, questions, and slides.</p></a>
      <a class="feature portal-module" href="#presentations"><strong>My Presentations</strong><p>Review selected and completed topics.</p></a>
      <div class="feature portal-module"><strong>Leadership Hours</strong><p><?php echo e($record['service_hours'] ?? '0'); ?> approved hours.</p></div>
      <div class="feature portal-module"><strong>Points & Tokens</strong><p><?php echo e((string) $points); ?> points and <?php echo e((string) $tokens); ?> tokens.</p></div>
      <div class="feature portal-module"><strong>Rewards</strong><p><?php echo e($record['reward_status'] ?? $rewardLevel); ?></p></div>
      <a class="feature portal-module" href="certificate.php?id=<?php echo e($studentId); ?>"><strong>Certificates</strong><p><?php echo e($record['certificate_status'] ?? 'Not Ready'); ?></p></a>
      <a class="feature portal-module" href="#resources"><strong>Resources</strong><p>Library links for research and preparation.</p></a>
      <a class="feature portal-module" href="#recordings"><strong>Session Recordings</strong><p>Watch approved session recordings.</p></a>
      <a class="feature portal-module" href="#announcements"><strong>Announcements</strong><p>Read updates from Yuva Club mentors.</p></a>
      <a class="feature portal-module" href="#safety-report"><strong>Report Issue</strong><p>Tell an adult moderator if something feels unsafe.</p></a>
    </div>
  </section>

  <section class="band">
    <div class="section-head">
      <h2>App Safety Rules</h2>
      <p>Yuva Club is designed for monitored learning. There is no private student chat, parent contact stays connected to each account, and every session should be supervised by an approved adult moderator.</p>
    </div>
    <div class="portal-module-grid">
      <div class="feature"><strong>No Private Chat</strong><p>Students communicate during supervised sessions and approved activities only.</p></div>
      <div class="feature"><strong>Parent Connected</strong><p>Parents can view progress, sessions, certificates, rewards, and feedback.</p></div>
      <div class="feature"><strong>Adult Moderation</strong><p>Admins can approve, suspend, remove, and review student participation.</p></div>
    </div>
  </section>

  <section class="band" id="topic-selection">
    <div class="two-grid">
      <form class="form-card" action="portal-submit-topic.php" method="post">
        <h2>Topic Selection</h2>
        <div class="field">
          <label for="topic_category">Topic Category *</label>
          <select id="topic_category" name="topic_category" required>
            <option value="">Select category</option>
            <?php foreach ($topics as $category => $items): ?>
              <option value="<?php echo e($category); ?>" <?php echo ($selection['topic_category'] ?? '') === $category ? 'selected' : ''; ?>><?php echo e($category); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="topic_title">Topic Title *</label>
          <select id="topic_title" name="topic_title" required></select>
        </div>
        <div class="field-grid">
          <div class="field">
            <label for="presentation_date">Presentation Date *</label>
            <input id="presentation_date" name="presentation_date" type="date" value="<?php echo e($selection['presentation_date'] ?? ''); ?>" required>
          </div>
          <div class="field">
            <label for="presentation_time">Presentation Time *</label>
            <input id="presentation_time" name="presentation_time" type="time" value="<?php echo e($selection['presentation_time'] ?? ''); ?>" required>
          </div>
        </div>
        <button class="button primary" type="submit">Save Topic</button>
      </form>

      <div class="form-card">
        <h2 id="presentations">My Presentations</h2>
        <?php if ($selection): ?>
          <p><strong>Category:</strong> <?php echo e($selection['topic_category']); ?></p>
          <p><strong>Title:</strong> <?php echo e($selection['topic_title']); ?></p>
          <p><strong>Date/Time:</strong> <?php echo e($selection['presentation_date']); ?> at <?php echo e($selection['presentation_time']); ?></p>
          <p><strong>Status:</strong> <?php echo e($selection['status'] ?? 'Pending Admin Review'); ?></p>
        <?php else: ?>
          <p>No topic selected yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="band" id="research-submission">
    <form class="form-card" action="portal-submit-research.php" method="post" enctype="multipart/form-data">
      <h2>Research Submission</h2>
      <div class="field">
        <label for="research_notes">Research Notes *</label>
        <textarea id="research_notes" name="research_notes" required><?php echo e($research['research_notes'] ?? ''); ?></textarea>
      </div>
      <div class="field">
        <label for="sources_used">Sources Used *</label>
        <textarea id="sources_used" name="sources_used" required><?php echo e($research['sources_used'] ?? ''); ?></textarea>
      </div>
      <div class="field">
        <label for="presentation_outline">Presentation Outline *</label>
        <textarea id="presentation_outline" name="presentation_outline" required><?php echo e($research['presentation_outline'] ?? ''); ?></textarea>
      </div>
      <div class="field">
        <label for="prepared_questions">Questions Prepared *</label>
        <textarea id="prepared_questions" name="prepared_questions" required><?php echo e($research['prepared_questions'] ?? ''); ?></textarea>
      </div>
      <div class="field">
        <label for="research_file">Upload File or Slides</label>
        <input id="research_file" name="research_file" type="file" accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png">
      </div>
      <?php if (!empty($research['file_original'])): ?>
        <p><strong>Current upload:</strong> <a href="portal-download.php?id=<?php echo e($studentId); ?>"><?php echo e($research['file_original']); ?></a></p>
      <?php endif; ?>
      <button class="button primary" type="submit">Submit Research</button>
    </form>
  </section>

  <section class="band" id="safety-report">
    <form class="form-card" action="portal-report-issue.php" method="post">
      <h2>Report Issue</h2>
      <p class="form-note">Use this if you saw something unsafe, confusing, or uncomfortable. A Yuva Club admin will review it.</p>
      <div class="field">
        <label for="report_type">Report Type *</label>
        <select id="report_type" name="report_type" required>
          <option value="">Select one</option>
          <option>Session behavior</option>
          <option>Technical problem</option>
          <option>Safety concern</option>
          <option>Content concern</option>
          <option>Other</option>
        </select>
      </div>
      <div class="field">
        <label for="report_message">What happened? *</label>
        <textarea id="report_message" name="report_message" required></textarea>
      </div>
      <button class="button primary" type="submit">Send Report</button>
    </form>
  </section>

  <section class="band">
    <div class="three-grid">
      <div class="form-card" id="announcements">
        <h2>Announcements</h2>
        <?php foreach (text_lines($hub['announcements']) as $line): ?>
          <p><?php echo e($line); ?></p>
        <?php endforeach; ?>
        <?php if (trim($hub['announcements']) === ''): ?><p>No announcements yet.</p><?php endif; ?>
      </div>
      <div class="form-card" id="recordings">
        <h2>Session Recordings</h2>
        <?php foreach (parse_link_lines($hub['recordings']) as $link): ?>
          <p><a href="<?php echo e($link['url']); ?>" target="_blank" rel="noopener"><?php echo e($link['title']); ?></a></p>
        <?php endforeach; ?>
        <?php if (trim($hub['recordings']) === ''): ?><p>No recordings posted yet.</p><?php endif; ?>
      </div>
      <div class="form-card" id="resources">
        <h2>Resources</h2>
        <?php foreach (parse_link_lines($hub['resources']) as $link): ?>
          <p><a href="<?php echo e($link['url']); ?>"><?php echo e($link['title']); ?></a></p>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<script>
const topicMap = <?php echo json_encode($topics); ?>;
const selectedTitle = <?php echo json_encode($selection['topic_title'] ?? ''); ?>;
const categorySelect = document.getElementById('topic_category');
const titleSelect = document.getElementById('topic_title');
function refreshTitles() {
  const titles = topicMap[categorySelect.value] || [];
  titleSelect.innerHTML = '<option value="">Select topic</option>';
  titles.forEach((title) => {
    const option = document.createElement('option');
    option.value = title;
    option.textContent = title;
    if (title === selectedTitle) option.selected = true;
    titleSelect.appendChild(option);
  });
}
categorySelect.addEventListener('change', refreshTitles);
refreshTitles();
</script>
<?php portal_footer(); ?>
