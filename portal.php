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
$certificateStatus = $record['certificate_status'] ?? 'Not Ready';
$certificateReady = in_array($certificateStatus, ['Ready', 'Issued'], true);
$aiReviewRecord = ai_reviews()[$studentId] ?? [];
$aiReviewApproved = ($aiReviewRecord['status'] ?? '') === 'Applied by Admin';
$approvedAiReview = $aiReviewApproved && is_array($aiReviewRecord['review'] ?? null) ? $aiReviewRecord['review'] : [];
$aiReviewState = !$research ? 'no-research' : ($aiReviewRecord === [] ? 'not-created' : ($aiReviewApproved ? 'approved' : ((($aiReviewRecord['status'] ?? '') === 'Needs Setup' || ($aiReviewRecord['error'] ?? '') !== '') ? 'unavailable' : 'awaiting-approval')));
$aiReviewDate = $aiReviewApproved ? ($aiReviewRecord['applied_at'] ?? $aiReviewRecord['reviewed_at'] ?? '') : '';
$aiResearchCategories = [
    'research_quality' => ['Research Quality', 20],
    'presentation_structure' => ['Presentation Structure', 20],
    'topic_understanding' => ['Topic Understanding', 20],
    'discussion_questions' => ['Discussion Questions', 15],
    'leadership_lesson' => ['Leadership Lesson', 15],
    'effort_and_readiness' => ['Effort & Readiness', 10],
];
$nextRank = next_rank_name($level);
$nextAction = [
    'title' => 'Choose your first presentation topic',
    'body' => 'Start by selecting a category, topic, date, and time so a mentor can review your plan.',
    'href' => '#topic-selection',
    'button' => 'Choose Topic',
];
if ($selection && !$research) {
    $nextAction = [
        'title' => 'Submit your research notes',
        'body' => 'Your topic is selected. Add notes, sources, outline, and prepared questions before presenting.',
        'href' => '#research-submission',
        'button' => 'Submit Research',
    ];
} elseif ($selection && $research) {
    $nextAction = [
        'title' => 'Prepare for your next presentation',
        'body' => 'Review your topic, check session details, and practice your speaking outline.',
        'href' => '#app-present',
        'button' => 'Review Presentation',
    ];
}

portal_header('Student Dashboard', true);
?>
<main id="app-main" tabindex="-1">
  <section class="band app-section" id="app-home" data-app-section="home">
    <?php
      $nameParts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
      $studentInitials = $nameParts ? strtoupper(substr($nameParts[0], 0, 1) . (count($nameParts) > 1 ? substr($nameParts[count($nameParts) - 1], 0, 1) : '')) : '?';
      $homeSessionTitle = $hasStudentZoom ? ($studentSessionTitle ?: 'Yuva Club Session') : ($session['title'] ?? 'Yuva Club Session');
      $homeSessionDate = $hasStudentZoom ? $studentSessionDate : ($session['date'] ?? '');
      $homeSessionStart = $hasStudentZoom ? $studentSessionStart : ($session['start'] ?? '');
      $homeSessionEnd = $hasStudentZoom ? $studentSessionEnd : ($session['end'] ?? '');
      $homeSessionStatus = $hasStudentZoom ? $studentSessionStatus : ($session['status'] ?? 'Not scheduled');
      $homeAnnouncements = text_lines($hub['announcements']);
    ?>
    <div class="home-welcome">
      <div class="home-welcome-copy">
        <p class="home-greeting">Good morning,</p>
        <h1><?php echo e($name); ?>!</h1>
        <p>Keep learning, keep growing.<br>Your future is yours to build.</p>
      </div>
      <div class="home-welcome-actions">
        <a class="home-notification" href="#announcements" aria-label="View announcements">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?php if ($homeAnnouncements): ?><span class="home-notification-dot" aria-hidden="true"></span><?php endif; ?>
        </a>
        <a class="home-avatar" href="#app-profile" aria-label="Open student profile"><?php echo e($studentInitials); ?></a>
      </div>
      <img class="home-welcome-art" src="assets/logo.png" alt="" aria-hidden="true">
      <span class="home-spark home-spark-one" aria-hidden="true"></span>
      <span class="home-spark home-spark-two" aria-hidden="true"></span>
      <span class="home-spark home-spark-three" aria-hidden="true"></span>
    </div>

    <?php if ($status === 'topic-saved'): ?><div class="form-status success" role="status" aria-live="polite">Topic selection saved.</div><?php endif; ?>
    <?php if ($status === 'topic-taken'): ?><div class="form-status error" role="alert">This topic is already selected by another student. Please choose a different topic.</div><?php endif; ?>
    <?php if ($status === 'research-saved'): ?><div class="form-status success" role="status" aria-live="polite">Research submission saved.</div><?php endif; ?>
    <?php if ($status === 'upload-error'): ?><div class="form-status error" role="alert">Research saved, but the upload file type was not accepted.</div><?php endif; ?>
    <?php if ($status === 'report-sent'): ?><div class="form-status success" role="status" aria-live="polite">Your report was sent to the Yuva Club admin team.</div><?php endif; ?>
    <?php if ($status === 'security-error'): ?><div class="form-status error" role="alert">This form expired. Please try again.</div><?php endif; ?>
    <?php if ($status === 'error'): ?><div class="form-status error" role="alert">Please complete all required fields.</div><?php endif; ?>
    <?php if ($status === 'certificate-not-ready'): ?><div class="form-status" role="status" aria-live="polite">Your certificate is not available yet. It will open after your progress is reviewed and the certificate is marked ready.</div><?php endif; ?>

    <div class="home-metrics" aria-label="Student achievements">
      <div class="home-metric"><span>Points</span><strong><?php echo e((string) $points); ?></strong></div>
      <div class="home-metric"><span>Tokens</span><strong><?php echo e((string) $tokens); ?></strong></div>
      <div class="home-metric"><span>Leadership level</span><strong><?php echo e($level); ?></strong></div>
      <div class="home-metric"><span>Streak</span><strong>Not tracked yet</strong></div>
    </div>

    <div class="home-dashboard-grid">
      <div class="form-card next-action-card home-card-wide">
        <p class="eyebrow">Next action</p>
        <h2><?php echo e($nextAction['title']); ?></h2>
        <p><?php echo e($nextAction['body']); ?></p>
        <a class="button primary" href="<?php echo e($nextAction['href']); ?>"><?php echo e($nextAction['button']); ?></a>
      </div>

      <div class="form-card home-quick-access">
        <p class="eyebrow">Quick access</p>
        <h2>Jump back in</h2>
        <div class="home-quick-links">
          <a href="#app-practice"><span>Practice</span><small>Choose a topic or submit research</small></a>
          <a href="#app-present"><span>Present</span><small>View sessions and presentations</small></a>
          <a href="#app-progress"><span>Progress</span><small>See your challenge journey</small></a>
          <?php if ($certificateReady): ?><a href="certificate.php?id=<?php echo e($studentId); ?>"><span>Certificate</span><small><?php echo e($certificateStatus); ?></small></a><?php else: ?><a href="#app-achievements"><span>Certificate</span><small><?php echo e($certificateStatus); ?> — view status</small></a><?php endif; ?>
        </div>
      </div>

      <div class="form-card home-session-card">
        <p class="eyebrow">Upcoming session</p>
        <h2><?php echo e($homeSessionTitle); ?></h2>
        <p><strong><?php echo e($homeSessionDate ?: 'Date to be announced'); ?></strong></p>
        <p><?php echo e($homeSessionStart ?: '--:--'); ?> - <?php echo e($homeSessionEnd ?: '--:--'); ?></p>
        <p class="home-status"><span>Status</span><?php echo e($homeSessionStatus); ?></p>
        <a class="button ghost" href="#app-present">View session</a>
      </div>

      <div class="form-card home-progress-card">
        <p class="eyebrow">Current progress</p>
        <h2><?php echo e($challengeStage); ?></h2>
        <div class="home-progress-list">
          <p><span>Presentations</span><strong><?php echo e($record['presentations'] ?? '0'); ?></strong></p>
          <p><span>Attendance</span><strong><?php echo e($record['attendance'] ?? '0'); ?> sessions</strong></p>
          <p><span>Service hours</span><strong><?php echo e($record['service_hours'] ?? '0'); ?> hours</strong></p>
          <p><span>Rubric score</span><strong><?php echo e((string) $rubricScore); ?> / 100</strong></p>
        </div>
        <a class="button ghost" href="#app-progress">View full progress</a>
      </div>

      <div class="form-card home-announcements home-card-wide">
        <p class="eyebrow">Announcements</p>
        <h2>Club updates</h2>
        <?php if ($homeAnnouncements): ?>
          <div class="home-announcement-list">
            <?php foreach ($homeAnnouncements as $line): ?><p><?php echo e($line); ?></p><?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>No announcements yet.</p>
        <?php endif; ?>
        <a class="button ghost" href="#announcements">View announcements</a>
      </div>
    </div>
  </section>

  <section class="band app-section" id="app-progress" data-app-section="progress">
    <?php
      $rankDefinitions = rank_definitions();
      $currentRankInfo = $rankDefinitions[$level] ?? $rankDefinitions['Explorer'];
      $nextRankInfo = $rankDefinitions[$nextRank] ?? $currentRankInfo;
      $certificateTitle = $currentRankInfo['certificate'] ?? 'Certificate of Participation';
    ?>
    <div class="journey-hero">
      <div class="journey-hero-copy"><p class="eyebrow">Leadership Journey</p><h1>You're becoming a leader.</h1><p>Every presentation, act of service, and brave new step helps your leadership grow.</p><div class="journey-hero-status"><span>Leadership Level<strong><?php echo e($level); ?></strong></span><span>Challenge Stage<strong><?php echo e($challengeStage); ?></strong></span></div></div>
      <img src="assets/student-leadership-journey-illustration.svg" alt="" aria-hidden="true">
    </div>

    <div class="journey-section-heading"><p class="eyebrow">Growth Snapshot</p><h2>Your progress at a glance</h2></div>
    <div class="journey-metrics" aria-label="Student growth metrics">
      <article class="journey-metric journey-metric-points"><span class="journey-metric-icon" aria-hidden="true"></span><p>Points</p><strong><?php echo e((string) $points); ?></strong></article>
      <article class="journey-metric journey-metric-tokens"><span class="journey-metric-icon" aria-hidden="true"></span><p>Tokens</p><strong><?php echo e((string) $tokens); ?></strong></article>
      <article class="journey-metric journey-metric-attendance"><span class="journey-metric-icon" aria-hidden="true"></span><p>Attendance</p><strong><?php echo e($record['attendance'] ?? '0'); ?><small> sessions</small></strong></article>
      <article class="journey-metric journey-metric-hours"><span class="journey-metric-icon" aria-hidden="true"></span><p>Volunteer Hours</p><strong><?php echo e($record['service_hours'] ?? '0'); ?><small> hours</small></strong></article>
      <article class="journey-metric journey-metric-presentations"><span class="journey-metric-icon" aria-hidden="true"></span><p>Presentations</p><strong><?php echo e($record['presentations'] ?? '0'); ?></strong></article>
    </div>

    <div class="journey-primary-grid">
      <article class="journey-card journey-rank-card">
        <div class="journey-card-heading"><span class="journey-card-icon journey-rank-icon" aria-hidden="true"></span><div><p class="eyebrow">Leadership Path</p><h2><?php echo e($level); ?></h2><p><?php echo e($currentRankInfo['meaning']); ?></p></div></div>
        <div class="journey-rank-details"><p><span>Approved Rank</span><strong><?php echo e($level); ?></strong></p><p><span>Rank Status</span><strong><?php echo e($record['rank_status'] ?? 'Approved'); ?></strong></p><p><span>Eligible Rank</span><strong><?php echo e($eligibleRank); ?></strong></p><p><span>Next Rank</span><strong><?php echo e($nextRank === $level ? 'Continued mentorship' : $nextRank); ?></strong></p></div>
        <div class="journey-requirement"><span><?php echo $nextRank === $level ? 'Continue growing' : 'Requirements for ' . e($nextRank); ?></span><p><?php echo e($nextRank === $level ? $currentRankInfo['requirements'] : $nextRankInfo['requirements']); ?></p></div>
      </article>

      <article class="journey-card journey-achievement-card">
        <div class="journey-card-heading"><span class="journey-card-icon journey-certificate-icon" aria-hidden="true"></span><div><p class="eyebrow">Achievement</p><h2><?php echo e($certificateTitle); ?></h2><p>Your current leadership certificate.</p></div></div>
        <div class="journey-certificate-status"><span>Certificate Status</span><strong><?php echo e($certificateStatus); ?></strong></div>
        <div class="journey-card-actions"><a class="button primary" href="#app-achievements">Explore Achievements</a><?php if ($certificateReady): ?><a class="button ghost" href="certificate.php?id=<?php echo e($studentId); ?>">View Certificate</a><?php else: ?><span class="certificate-unavailable" role="status">Certificate available after approval</span><?php endif; ?></div>
      </article>
    </div>

    <article class="journey-card journey-challenge-card">
      <div class="journey-card-heading"><span class="journey-card-icon journey-challenge-icon" aria-hidden="true"></span><div><p class="eyebrow">Challenge Journey</p><h2><?php echo e($challengeStage); ?></h2><p>The Global Youth Speaking &amp; Leadership Challenge</p></div></div>
      <div class="journey-challenge-meta"><p><span>Month</span><strong><?php echo e($record['challenge_month'] ?? date('Y-m')); ?></strong></p><p><span>Region</span><strong><?php echo e(($record['challenge_region'] ?? '') !== '' ? $record['challenge_region'] : 'Not assigned'); ?></strong></p><p><span>Finalist Status</span><strong><?php echo e($record['finalist_status'] ?? 'Not Qualified'); ?></strong></p><p><span>Award Status</span><strong><?php echo e($record['award_status'] ?? 'None'); ?></strong></p></div>
      <div class="journey-stage-path" aria-label="Challenge stages"><?php foreach (challenge_stages() as $stage): ?><div class="<?php echo $stage === $challengeStage ? 'is-current' : ''; ?>"><span aria-hidden="true"></span><strong><?php echo e($stage); ?></strong></div><?php endforeach; ?></div>
    </article>

    <div class="journey-section-heading"><p class="eyebrow">Badges</p><h2>Milestones you've earned</h2></div>
    <?php if ($badges): ?><div class="journey-badge-grid"><?php foreach ($badges as $index => $badge): ?><article class="journey-badge journey-badge-<?php echo e((string) (($index % 5) + 1)); ?>"><span aria-hidden="true"></span><strong><?php echo e($badge); ?></strong><small>Earned</small></article><?php endforeach; ?></div><?php else: ?><div class="journey-empty-state"><span class="journey-empty-icon" aria-hidden="true"></span><div><h2>Your first badge is waiting</h2><p>Your first badge will appear as you participate and complete real milestones.</p></div></div><?php endif; ?>

    <div class="journey-section-heading journey-rubric-heading"><p class="eyebrow">Official Presentation Rubric</p><h2>Your evaluated presentation skills</h2><p>This is the official YUVA Club presentation evaluation.</p></div>
    <div class="journey-rubric-layout">
      <article class="journey-card journey-rubric-card"><div class="journey-rubric-total"><span>Total Score</span><strong><?php echo e((string) $rubricScore); ?><small>/100</small></strong><p><?php echo e((string) $rubricCompleted); ?> of <?php echo e((string) count(rubric_categories())); ?> categories scored</p></div><div class="journey-rubric-list"><?php foreach (rubric_categories() as $rubricKey => $rubricLabel): ?><p><span><?php echo e($rubricLabel); ?></span><strong><?php echo ($record['rubric_' . $rubricKey] ?? '') !== '' ? e((string) $record['rubric_' . $rubricKey]) . ' / 10' : 'Not scored'; ?></strong></p><?php endforeach; ?></div></article>
      <article class="journey-card journey-feedback-card"><div class="journey-card-heading"><span class="journey-card-icon journey-feedback-icon" aria-hidden="true"></span><div><p class="eyebrow">Judge Feedback</p><h2>Guidance for your next step</h2></div></div><p><?php echo e(($record['judge_feedback'] ?? '') !== '' ? $record['judge_feedback'] : 'Challenge feedback will appear after a mentor or judge reviews your presentation.'); ?></p><div class="journey-award-status"><span>Award Status</span><strong><?php echo e($record['award_status'] ?? 'None'); ?></strong></div></article>
    </div>

    <article class="journey-leaderboard-card"><div><p class="eyebrow">Leadership Challenge</p><h2>See the challenge leaderboard</h2><p>Explore approved progress by program and challenge stage. Your position is calculated on the leaderboard page.</p></div><a class="button primary" href="leaderboard.php">View Leaderboard</a></article>

    <div class="journey-section-heading journey-future-heading"><p class="eyebrow">Future Growth</p><h2>More ways to build momentum</h2><p>These capabilities are planned for future YUVA Club updates.</p></div>
    <div class="journey-roadmap-grid"><article class="journey-roadmap journey-roadmap-goals"><span class="journey-roadmap-icon" aria-hidden="true"></span><div><h3>Weekly Goals</h3><p>Set and track weekly leadership activities.</p></div><strong>Coming Soon</strong></article><article class="journey-roadmap journey-roadmap-streak"><span class="journey-roadmap-icon" aria-hidden="true"></span><div><h3>Streak Tracking</h3><p>Celebrate consistent participation over time.</p></div><strong>Coming Soon</strong></article><article class="journey-roadmap journey-roadmap-rewards"><span class="journey-roadmap-icon" aria-hidden="true"></span><div><h3>Token Rewards</h3><p>Use tokens with future approved rewards.</p></div><strong>Future Update</strong></article></div>
  </section>

  <section class="band app-section" id="app-achievements" data-app-section="progress">
    <?php
      $achievementRankInfo = rank_definitions()[$level] ?? rank_definitions()['Explorer'];
      $achievementCertificateTitle = $achievementRankInfo['certificate'] ?? 'Certificate of Participation';
      $achievementCertificateStatus = $record['certificate_status'] ?? 'Not Ready';
      $achievementNotes = [
          'Mentor Recognition' => trim((string) ($record['mentor_feedback'] ?? '')),
          'Teacher Recognition' => trim((string) ($record['teacher_feedback'] ?? '')),
          'Judge Recognition' => trim((string) ($record['judge_feedback'] ?? '')),
      ];
      $hasAchievementNotes = count(array_filter($achievementNotes, fn($note) => $note !== '')) > 0;
    ?>
    <div class="achievements-hero">
      <div class="achievements-hero-copy"><a class="achievements-back" href="#app-progress">← Leadership Journey</a><p class="eyebrow">Achievements</p><h1>Celebrate how far you've come.</h1><p>Your presentations, service, badges, and leadership growth tell the story of the leader you are becoming.</p><div class="achievements-hero-stats"><span>Leadership Rank<strong><?php echo e($level); ?></strong></span><span>Badges Earned<strong><?php echo e((string) count($badges)); ?></strong></span></div></div>
      <img src="assets/student-achievements-illustration.svg" alt="" aria-hidden="true">
    </div>

    <div class="achievements-section-heading"><p class="eyebrow">Recognition Snapshot</p><h2>Your proud moments</h2></div>
    <div class="achievements-metrics">
      <article class="achievement-metric achievement-metric-badges"><span class="achievement-metric-icon" aria-hidden="true"></span><p>Earned Badges</p><strong><?php echo e((string) count($badges)); ?></strong></article>
      <article class="achievement-metric achievement-metric-presentations"><span class="achievement-metric-icon" aria-hidden="true"></span><p>Presentations</p><strong><?php echo e($record['presentations'] ?? '0'); ?></strong></article>
      <article class="achievement-metric achievement-metric-hours"><span class="achievement-metric-icon" aria-hidden="true"></span><p>Volunteer Hours</p><strong><?php echo e($record['service_hours'] ?? '0'); ?></strong></article>
      <article class="achievement-metric achievement-metric-attendance"><span class="achievement-metric-icon" aria-hidden="true"></span><p>Attendance</p><strong><?php echo e($record['attendance'] ?? '0'); ?></strong></article>
      <article class="achievement-metric achievement-metric-rubric"><span class="achievement-metric-icon" aria-hidden="true"></span><p>Rubric Score</p><strong><?php echo e((string) $rubricScore); ?><small>/100</small></strong></article>
    </div>

    <article class="achievements-certificate-card">
      <div class="achievements-certificate-art" aria-hidden="true"><span></span></div>
      <div class="achievements-certificate-copy"><p class="eyebrow">Current Certificate</p><h2><?php echo e($achievementCertificateTitle); ?></h2><p>Recognizing your current approved leadership rank and participation in YUVA Club.</p><div class="achievements-certificate-meta"><span>Certificate Status<strong><?php echo e($achievementCertificateStatus); ?></strong></span><span>Leadership Rank<strong><?php echo e($level); ?></strong></span></div><?php if (!$certificateReady): ?><p class="achievements-honest-note" role="status">Your current achievement certificate will become available after the required progress is reviewed and approved.</p><?php endif; ?><?php if ($certificateReady): ?><div class="achievements-actions"><a class="button primary" href="certificate.php?id=<?php echo e($studentId); ?>">View Certificate</a><a class="button ghost" href="certificate.php?id=<?php echo e($studentId); ?>" target="_blank" rel="noopener">Open to Print</a></div><?php endif; ?></div>
    </article>

    <div class="achievements-section-heading"><p class="eyebrow">Earned Badges</p><h2>Milestones worth celebrating</h2><p>Only badges earned from your real YUVA Club progress appear here.</p></div>
    <?php if ($badges): ?><div class="achievements-badge-grid"><?php foreach ($badges as $index => $badge): ?><article class="achievements-badge achievements-badge-<?php echo e((string) (($index % 5) + 1)); ?>"><span class="achievements-badge-shield" aria-hidden="true"><i></i></span><h3><?php echo e($badge); ?></h3><strong>Earned</strong></article><?php endforeach; ?></div><?php else: ?><div class="achievements-empty"><span aria-hidden="true"></span><div><h2>Your first badge is waiting</h2><p>Your first badge will appear as you participate and complete real milestones.</p></div></div><?php endif; ?>

    <div class="achievements-two-grid">
      <article class="achievements-card achievements-recognition-card"><div class="achievements-card-heading"><span class="achievements-card-icon achievements-recognition-icon" aria-hidden="true"></span><div><p class="eyebrow">Leadership Recognition</p><h2>Your leadership story</h2></div></div><div class="achievements-detail-list"><p><span>Leadership Milestone</span><strong><?php echo e(($record['leadership_milestones'] ?? '') !== '' ? $record['leadership_milestones'] : 'Your leadership milestone summary has not been recorded yet.'); ?></strong></p><p><span>Rank Recommendation</span><strong><?php echo e(($record['rank_recommendation'] ?? '') !== '' ? $record['rank_recommendation'] : 'Your next-rank recommendation has not been reviewed yet.'); ?></strong></p><p><span>Challenge Stage</span><strong><?php echo e($challengeStage); ?></strong></p><p><span>Finalist Status</span><strong><?php echo e($record['finalist_status'] ?? 'Not Qualified'); ?></strong></p><p><span>Award Status</span><strong><?php echo e($record['award_status'] ?? 'None'); ?></strong></p></div></article>
      <article class="achievements-card achievements-evidence-card"><div class="achievements-card-heading"><span class="achievements-card-icon achievements-evidence-icon" aria-hidden="true"></span><div><p class="eyebrow">Growth Evidence</p><h2>Your current contribution</h2></div></div><div class="achievements-detail-list"><p><span>Presentations</span><strong><?php echo e($record['presentations'] ?? '0'); ?></strong></p><p><span>Volunteer/Leadership Hours</span><strong><?php echo e($record['service_hours'] ?? '0'); ?></strong></p><p><span>Sessions Attended</span><strong><?php echo e($record['attendance'] ?? '0'); ?></strong></p><p><span>Current Topic</span><strong><?php echo e($selection['topic_title'] ?? 'No topic selected yet'); ?></strong></p><p><span>Official Rubric</span><strong><?php echo e((string) $rubricScore); ?> / 100</strong></p></div></article>
    </div>

    <div class="achievements-section-heading"><p class="eyebrow">Recognition Notes</p><h2>Encouragement from your community</h2></div>
    <?php if ($hasAchievementNotes): ?><div class="achievements-notes-grid"><?php foreach ($achievementNotes as $noteTitle => $noteText): ?><?php if ($noteText !== ''): ?><article class="achievements-note"><span aria-hidden="true"></span><h3><?php echo e($noteTitle); ?></h3><p><?php echo e($noteText); ?></p></article><?php endif; ?><?php endforeach; ?></div><?php else: ?><div class="achievements-empty achievements-notes-empty"><span aria-hidden="true"></span><div><h2>Recognition notes will appear here</h2><p>Recognition notes will appear after a mentor, teacher, or judge reviews your progress.</p></div></div><?php endif; ?>

    <div class="achievements-section-heading achievements-future-heading"><p class="eyebrow">Future Portfolio</p><h2>More ways to preserve your journey</h2><p>These portfolio capabilities are planned for future YUVA Club updates.</p></div>
    <div class="achievements-roadmap-grid"><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Certificate History</h3><p>Review certificates earned over time.</p></div><strong>Coming Soon</strong></article><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Achievement Timeline</h3><p>See milestones in a future dated timeline.</p></div><strong>Coming Soon</strong></article><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Share Achievements</h3><p>Share approved recognition safely.</p></div><strong>Future Update</strong></article><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Download Portfolio</h3><p>Create a future portable achievement record.</p></div><strong>Future Update</strong></article></div>
  </section>

  <section class="band app-section" id="app-present" data-app-section="present">
    <div class="present-hero">
      <div class="present-hero-copy"><p class="eyebrow">Presentation Center</p><h1>Your voice inspires!</h1><p>Prepare your ideas, join your session, and lead the change.</p></div>
      <img src="assets/student-presentation-illustration.svg" alt="" aria-hidden="true">
    </div>

    <div class="present-center-grid">
      <section class="present-session-card present-card-wide" aria-labelledby="present-upcoming-title">
        <div class="present-card-heading"><span class="present-icon present-icon-calendar" aria-hidden="true"></span><div><p class="eyebrow">Upcoming Presentation</p><h2 id="present-upcoming-title"><?php echo e($hasStudentZoom ? ($studentSessionTitle ?: 'Yuva Club Session') : ($session['title'] ?? 'Yuva Club Session')); ?></h2></div></div>
        <?php if ($hasStudentZoom): ?>
          <div class="present-session-details"><p><span>Presentation Schedule</span><strong><?php echo e($studentSessionDate ?: 'Date to be announced'); ?></strong><small><?php echo e($studentSessionStart ?: '--:--'); ?> - <?php echo e($studentSessionEnd ?: '--:--'); ?></small></p><p><span>Session Status</span><strong><?php echo e($studentSessionStatus); ?></strong></p></div>
          <?php if ($studentZoomMeetingId !== '' || $studentZoomPassword !== ''): ?>
            <p class="present-credentials">
              <?php if ($studentZoomMeetingId !== ''): ?><span>Meeting ID</span><strong><?php echo e($studentZoomMeetingId); ?></strong><?php endif; ?>
              <?php if ($studentZoomPassword !== ''): ?><span>Password</span><strong><?php echo e($studentZoomPassword); ?></strong><?php endif; ?>
            </p>
          <?php endif; ?>
          <?php if ($effectiveZoomUrl !== ''): ?>
            <div class="present-join-actions">
              <a class="button primary present-zoom-button" href="<?php echo e($effectiveZoomUrl); ?>" target="_blank" rel="noopener"><span class="present-icon present-icon-video" aria-hidden="true"></span>Join Zoom</a>
              <?php if ($effectiveBrowserZoomUrl !== ''): ?>
                <a class="button ghost" href="<?php echo e($effectiveBrowserZoomUrl); ?>" target="_blank" rel="noopener">Join from Browser</a>
              <?php endif; ?>
            </div>
          <?php else: ?><p class="present-empty-note">The Zoom link has not been posted yet.</p>
          <?php endif; ?>
        <?php else: ?>
          <div class="present-session-details"><p><span>Presentation Schedule</span><strong><?php echo e($session['date'] ?: 'Date to be announced'); ?></strong><small><?php echo e($session['start'] ?: '--:--'); ?> - <?php echo e($session['end'] ?: '--:--'); ?></small></p><p><span>Session Status</span><strong><?php echo e($session['status']); ?></strong></p></div>
          <p class="present-empty-note">Your next personal presentation session has not been scheduled yet.</p>
          <?php if (($session['zoom_url'] ?? '') !== ''): ?><div class="present-join-actions"><a class="button primary present-zoom-button" href="<?php echo e($session['zoom_url']); ?>" target="_blank" rel="noopener"><span class="present-icon present-icon-video" aria-hidden="true"></span>Join Zoom</a><?php $groupBrowserZoomUrl = zoom_browser_join_url($session['zoom_url'] ?? ''); ?><?php if ($groupBrowserZoomUrl !== ''): ?><a class="button ghost" href="<?php echo e($groupBrowserZoomUrl); ?>" target="_blank" rel="noopener">Join from Browser</a><?php endif; ?></div><?php endif; ?>
        <?php endif; ?>
      </section>

      <article class="present-info-card present-topic-card"><span class="present-icon present-icon-topic" aria-hidden="true"></span><div><p class="eyebrow">Presentation Topic</p><?php if ($selection): ?><h2><?php echo e($selection['topic_title']); ?></h2><p><?php echo e($selection['topic_category']); ?></p><small><?php echo e($selection['presentation_date']); ?> at <?php echo e($selection['presentation_time']); ?> · <?php echo e($selection['status'] ?? 'Pending Admin Review'); ?></small><?php else: ?><h2>No topic selected yet</h2><p>Choose a topic in your Practice Workspace to prepare for your next presentation.</p><a href="#topic-selection">Choose a Topic</a><?php endif; ?></div></article>

      <article class="present-info-card present-readiness-card"><span class="present-icon present-icon-check" aria-hidden="true"></span><div><p class="eyebrow">Research Readiness</p><h2><?php echo e($research['status'] ?? 'Not Submitted'); ?></h2><p><?php echo $research ? 'Your saved preparation is ready to review.' : 'Build your notes, sources, outline, and questions in Practice Workspace.'; ?></p><a href="#research-submission">Open Research Workspace</a></div></article>

      <article class="present-info-card present-upload-card"><span class="present-icon present-icon-upload" aria-hidden="true"></span><div><p class="eyebrow">Upload Slides</p><h2><?php echo !empty($research['file_original']) ? e($research['file_original']) : 'No slides uploaded yet'; ?></h2><p>Upload or replace PDF, PowerPoint, document, or image files using the existing Research upload.</p><?php if (!empty($research['file_original'])): ?><a href="portal-download.php?id=<?php echo e($studentId); ?>">View current upload</a><?php endif; ?><a href="#research-submission">Upload or Replace Slides</a></div></article>

      <article class="present-info-card present-questions-card"><span class="present-icon present-icon-question" aria-hidden="true"></span><div><p class="eyebrow">Prepared Questions</p><?php if (!empty($research['prepared_questions'])): ?><h2>Questions ready</h2><p class="present-question-preview"><?php echo nl2br(e($research['prepared_questions'])); ?></p><?php else: ?><h2>No questions prepared yet</h2><p>Add possible audience questions and your answers in Practice Workspace.</p><?php endif; ?><a href="#research-submission">Review Questions</a></div></article>

      <section class="present-scheduler-card present-card-wide" aria-labelledby="present-schedule-title"><div class="present-card-heading"><span class="present-icon present-icon-schedule" aria-hidden="true"></span><div><p class="eyebrow">Presentation Schedule</p><h2 id="present-schedule-title">Schedule your session</h2><p>Use the official YUVA Club Zoom Scheduler.</p></div></div>
      <?php if ($schedulerSrc !== ''): ?>
        <a class="button ghost present-scheduler-link" href="<?php echo e($schedulerPageUrl ?: $schedulerSrc); ?>" target="_blank" rel="noopener">Open Scheduler in New Tab</a>
        <div class="zoom-scheduler-frame">
          <iframe src="<?php echo e($schedulerSrc); ?>" frameborder="0" width="750" height="560" title="Yuva Club Zoom Scheduler"></iframe>
        </div>
      <?php else: ?>
        <p class="present-empty-note">The Zoom Scheduler will appear here after admin adds it for your group.</p>
      <?php endif; ?>
      </section>

      <div class="present-future-heading present-card-wide"><p class="eyebrow">Coming to Presentation Center</p><h2>More ways to grow your voice</h2><p>These capabilities are planned for future YUVA Club updates.</p></div>
      <div class="present-future-grid present-card-wide">
        <article class="present-future-card present-future-timer"><span class="present-icon" aria-hidden="true"></span><div><h3>Presentation Timer</h3><p>Practice pacing before your live session.</p></div><strong>Coming Soon</strong></article>
        <article class="present-future-card present-future-history"><span class="present-icon" aria-hidden="true"></span><div><h3>Past Presentations</h3><p>Review individual sessions and feedback.</p></div><strong>Coming Soon</strong></article>
        <article class="present-future-card present-future-replay"><span class="present-icon" aria-hidden="true"></span><div><h3>Replay</h3><p>Watch saved presentation recordings.</p></div><strong>Coming Soon</strong></article>
        <article class="present-future-card present-future-analytics"><span class="present-icon" aria-hidden="true"></span><div><h3>Presentation Analytics</h3><p>Explore delivery insights and growth trends.</p></div><strong>Future Update</strong></article>
      </div>
    </div>
  </section>

  <section class="band app-section" id="app-ai-coach" data-app-section="practice">
    <div class="ai-studio-hero">
      <div><p class="eyebrow">AI Coach Studio</p><h1>Grow your voice with thoughtful feedback.</h1><p>Your approved coaching review brings together research insights, strengths, and clear next steps.</p></div>
      <img src="assets/student-ai-coach-illustration.svg" alt="" aria-hidden="true">
    </div>

    <?php if ($aiReviewState !== 'approved'): ?>
      <div class="ai-studio-state ai-state-<?php echo e($aiReviewState); ?>">
        <span class="ai-studio-state-icon" aria-hidden="true"></span>
        <div>
          <?php if ($aiReviewState === 'no-research'): ?><p class="eyebrow">Start with research</p><h2>No research submitted</h2><p>Complete your Research Workspace before an AI Coach review can be prepared.</p><a class="button primary" href="#research-submission">Open Research Workspace</a>
          <?php elseif ($aiReviewState === 'not-created'): ?><p class="eyebrow">Coach review</p><h2>No AI review created yet</h2><p>Your approved coaching feedback will appear here after an administrator creates and reviews it.</p>
          <?php elseif ($aiReviewState === 'awaiting-approval'): ?><p class="eyebrow">Adult review in progress</p><h2>Your feedback is being reviewed</h2><p>An administrator is checking the AI Coach draft before it becomes visible to you.</p>
          <?php else: ?><p class="eyebrow">Coach review</p><h2>Review temporarily unavailable</h2><p>Your coaching review cannot be displayed right now. Please check back later.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="ai-studio-approved-banner"><span class="ai-approved-mark" aria-hidden="true"></span><div><p class="eyebrow">Approved review available</p><strong>Reviewed and approved by a YUVA Club administrator</strong></div><small><?php echo e($aiReviewDate); ?></small></div>

      <div class="ai-studio-overview">
        <article class="ai-score-card">
          <p class="eyebrow">Overall Score</p>
          <div class="ai-score-ring" style="--ai-score: <?php echo e((string) max(0, min(100, (int) ($approvedAiReview['total_points'] ?? 0)))); ?>"><strong><?php echo e((string) ($approvedAiReview['total_points'] ?? '0')); ?></strong><span>/100</span></div>
          <div><h2>Keep growing!</h2><p><?php echo e($approvedAiReview['summary'] ?? 'Your approved AI Coach summary will appear here.'); ?></p><span class="ai-token-award"><?php echo e((string) ($approvedAiReview['suggested_tokens'] ?? '0')); ?> approved tokens</span></div>
        </article>
        <article class="ai-topic-card"><p class="eyebrow">Reviewed Preparation</p><h2><?php echo e($aiReviewRecord['topic_title'] ?? ($selection['topic_title'] ?? 'Presentation Research')); ?></h2><p><?php echo e($aiReviewRecord['topic_category'] ?? ($selection['topic_category'] ?? '')); ?></p><small>Approval status: <?php echo e($aiReviewRecord['status']); ?></small></article>
      </div>

      <div class="ai-studio-section-heading"><p class="eyebrow">AI Coach Research Review</p><h2>Preparation insights</h2><p>This review evaluates the student’s submitted research and presentation preparation.</p></div>
      <div class="ai-research-review-card">
        <?php foreach ($aiResearchCategories as $aiKey => [$aiLabel, $aiMaximum]): ?>
          <?php $aiCategoryScore = max(0, min($aiMaximum, (int) ($approvedAiReview[$aiKey] ?? 0))); ?>
          <div class="ai-research-metric"><div><span><?php echo e($aiLabel); ?></span><strong><?php echo e((string) $aiCategoryScore); ?> / <?php echo e((string) $aiMaximum); ?></strong></div><div class="ai-metric-track"><i style="width: <?php echo e((string) round(($aiCategoryScore / $aiMaximum) * 100)); ?>%"></i></div></div>
        <?php endforeach; ?>
      </div>

      <div class="ai-feedback-grid">
        <article class="ai-feedback-card ai-strengths-card"><div class="ai-feedback-title"><span aria-hidden="true"></span><div><p class="eyebrow">Strengths</p><h2>What is working well</h2></div></div><?php if (!empty($approvedAiReview['strengths']) && is_array($approvedAiReview['strengths'])): ?><ul><?php foreach ($approvedAiReview['strengths'] as $strength): ?><li><?php echo e((string) $strength); ?></li><?php endforeach; ?></ul><?php else: ?><p>No detailed strengths were included in this approved review.</p><?php endif; ?></article>
        <article class="ai-feedback-card ai-improvements-card"><div class="ai-feedback-title"><span aria-hidden="true"></span><div><p class="eyebrow">Next Steps</p><h2>Ways to improve</h2></div></div><?php if (!empty($approvedAiReview['improvements']) && is_array($approvedAiReview['improvements'])): ?><ul><?php foreach ($approvedAiReview['improvements'] as $improvement): ?><li><?php echo e((string) $improvement); ?></li><?php endforeach; ?></ul><?php else: ?><p>No detailed improvements were included in this approved review.</p><?php endif; ?></article>
        <article class="ai-feedback-card ai-coaching-note"><p class="eyebrow">Communication Preparation</p><h2>Clarity and organization</h2><p><?php echo e($approvedAiReview['communication_skills'] ?? 'No communication preparation note was included.'); ?></p></article>
        <article class="ai-feedback-card ai-milestone-note"><p class="eyebrow">Leadership Milestone</p><h2>Your next leadership step</h2><p><?php echo e($approvedAiReview['leadership_milestones'] ?? 'No leadership milestone note was included.'); ?></p></article>
      </div>

      <div class="ai-studio-section-heading ai-rubric-heading"><p class="eyebrow">Presentation Rubric</p><h2>Official presentation evaluation</h2><p>This separate rubric is completed through the YUVA Club presentation evaluation process. It is not the AI research review above.</p></div>
      <div class="ai-presentation-rubric"><div class="ai-rubric-total"><span>Official rubric total</span><strong><?php echo e((string) $rubricScore); ?> <small>/ 100</small></strong><p><?php echo e((string) $rubricCompleted); ?> of <?php echo e((string) count(rubric_categories())); ?> categories scored</p></div><div class="ai-rubric-list"><?php foreach (rubric_categories() as $rubricKey => $rubricLabel): ?><p><span><?php echo e($rubricLabel); ?></span><strong><?php echo ($record['rubric_' . $rubricKey] ?? '') !== '' ? e((string) $record['rubric_' . $rubricKey]) . ' / 10' : 'Not scored'; ?></strong></p><?php endforeach; ?></div></div>
    <?php endif; ?>

    <div class="ai-studio-section-heading ai-roadmap-heading"><p class="eyebrow">Studio Roadmap</p><h2>Future coaching capabilities</h2><p>These experiences are planned for future YUVA Club updates.</p></div>
    <div class="ai-roadmap-grid">
      <?php foreach ([['replay','Replay','Review a presentation recording.','Coming Soon'],['video','Video Feedback','Receive visual delivery guidance.','Future Update'],['moments','Key Moments','Jump to important presentation moments.','Coming Soon'],['voice','Voice Analytics','Explore pacing and vocal delivery.','Future Update'],['confidence','Confidence Analytics','Understand confidence patterns.','Future Update'],['eye','Eye-Contact Analytics','Review audience engagement cues.','Future Update']] as [$roadmapClass,$roadmapTitle,$roadmapText,$roadmapStatus]): ?><article class="ai-roadmap-card ai-roadmap-<?php echo e($roadmapClass); ?>"><span class="ai-roadmap-icon" aria-hidden="true"></span><div><h3><?php echo e($roadmapTitle); ?></h3><p><?php echo e($roadmapText); ?></p></div><strong><?php echo e($roadmapStatus); ?></strong></article><?php endforeach; ?>
    </div>
  </section>

  <section class="band app-section" id="app-profile" data-app-section="profile">
    <?php
      $profileFullName = trim((string) (($student['Student First Name'] ?? '') . ' ' . ($student['Student Last Name'] ?? '')));
      $profilePreferredName = trim((string) ($student['Preferred Name'] ?? ''));
      $profileNameParts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
      $profileInitials = $profileNameParts ? strtoupper(substr($profileNameParts[0], 0, 1) . (count($profileNameParts) > 1 ? substr($profileNameParts[count($profileNameParts) - 1], 0, 1) : '')) : '?';
      $profileParentConnected = trim((string) ($student['Parent Email'] ?? '')) !== '' || trim((string) ($student['Parent/Guardian Name'] ?? '')) !== '';
      $profileValue = static fn(array $source, string $key, string $fallback): string => trim((string) ($source[$key] ?? '')) !== '' ? trim((string) $source[$key]) : $fallback;
    ?>
    <div class="profile-identity-header">
      <div class="profile-initials" aria-label="Student initials"><?php echo e($profileInitials); ?></div>
      <div class="profile-identity-copy"><p class="eyebrow">My Profile</p><h1><?php echo e($name); ?></h1><p>YUVA Club ID: <strong><?php echo e($studentId); ?></strong></p><div class="profile-identity-badges"><span><?php echo e($level); ?></span><span><?php echo e($membershipGroupLabel); ?></span></div></div>
    </div>

    <div class="profile-section-heading"><p class="eyebrow">Identity</p><h2>About me</h2><p>Your registered YUVA Club information is shown here as read-only.</p></div>
    <div class="profile-two-grid">
      <article class="profile-card profile-about-card"><div class="profile-card-heading"><span class="profile-card-icon profile-about-icon" aria-hidden="true"></span><div><h2>About Me</h2><p>The details that make your profile yours.</p></div></div><div class="profile-detail-list"><p><span>Full Name</span><strong><?php echo e($profileFullName !== '' ? $profileFullName : 'Full name has not been added yet.'); ?></strong></p><p><span>Preferred Name</span><strong><?php echo e($profilePreferredName !== '' ? $profilePreferredName : 'Preferred name has not been added yet.'); ?></strong></p><p><span>Grade</span><strong><?php echo e($profileValue($student, 'Grade', 'Grade has not been added yet.')); ?></strong></p><p><span>School</span><strong><?php echo e($profileValue($student, 'School', 'School information has not been added yet.')); ?></strong></p><p><span>City / State</span><strong><?php echo e($profileValue($student, 'City/State', 'Location has not been added yet.')); ?></strong></p><p><span>Interests</span><strong><?php echo e($profileValue($student, 'Interests', 'Interests have not been added yet.')); ?></strong></p><p><span>My Motivation</span><strong><?php echo e($profileValue($student, 'Why Join', 'Your motivation has not been recorded yet.')); ?></strong></p></div></article>

      <article class="profile-card profile-school-card"><div class="profile-card-heading"><span class="profile-card-icon profile-school-icon" aria-hidden="true"></span><div><h2>School &amp; Membership</h2><p>Your learning community.</p></div></div><div class="profile-detail-list"><p><span>School</span><strong><?php echo e($profileValue($student, 'School', 'School information has not been added yet.')); ?></strong></p><p><span>Grade</span><strong><?php echo e($profileValue($student, 'Grade', 'Grade has not been added yet.')); ?></strong></p><p><span>Program Group</span><strong><?php echo e($membershipGroupLabel); ?></strong></p><p><span>Membership Type</span><strong><?php echo e($profileValue($student, 'Membership Type', 'Membership type has not been added yet.')); ?></strong></p><p><span>Organization Code</span><strong><?php echo e($profileValue($student, 'Organization Code', 'No organization code connected.')); ?></strong></p></div></article>
    </div>

    <div class="profile-two-grid profile-contact-grid">
      <article class="profile-card"><div class="profile-card-heading"><span class="profile-card-icon profile-contact-icon" aria-hidden="true"></span><div><h2>Contact</h2><p>Your student contact information.</p></div></div><div class="profile-detail-list"><p><span>Student Email</span><strong><?php echo e($profileValue($student, 'Student Email', 'Student email has not been provided.')); ?></strong></p><p><span>Student Phone</span><strong><?php echo e($profileValue($student, 'Student Phone Number', 'Student phone has not been provided.')); ?></strong></p><p><span>WhatsApp</span><strong><?php echo e($profileValue($student, 'WhatsApp Username / Number', 'WhatsApp information has not been provided.')); ?></strong></p><p><span>Parent Connection</span><strong class="profile-connected-status"><?php echo $profileParentConnected ? 'Parent / guardian connected' : 'Parent / guardian connection not recorded'; ?></strong></p></div></article>

      <article class="profile-card"><div class="profile-card-heading"><span class="profile-card-icon profile-preferences-icon" aria-hidden="true"></span><div><h2>Participation Preferences</h2><p>Your current read-only participation details.</p></div></div><div class="profile-detail-list"><p><span>Preferred Schedule</span><strong><?php echo e($profileValue($student, 'Preferred Schedule', 'Schedule preferences have not been added yet.')); ?></strong></p><p><span>Presentation Experience</span><strong><?php echo e($profileValue($student, 'Presentation Experience', 'Presentation experience has not been recorded yet.')); ?></strong></p><p><span>Presentation Topics</span><strong><?php echo e($profileValue($student, 'Presentation Topics', 'Presentation-topic interests have not been added yet.')); ?></strong></p><p><span>Suggestions</span><strong><?php echo e($profileValue($student, 'Suggestions', 'No suggestions have been recorded.')); ?></strong></p></div><p class="profile-managed-note">Profile updates are currently managed by the YUVA Club team.</p></article>
    </div>

    <article class="profile-card profile-leadership-card"><div class="profile-card-heading"><span class="profile-card-icon profile-leadership-icon" aria-hidden="true"></span><div><p class="eyebrow">Leadership Identity</p><h2><?php echo e($level); ?></h2><p>A compact view of your current leadership identity.</p></div></div><div class="profile-leadership-summary"><p><span>Approved Rank</span><strong><?php echo e($level); ?></strong></p><p><span>Rank Status</span><strong><?php echo e($record['rank_status'] ?? 'Approved'); ?></strong></p><p><span>Leadership Milestone</span><strong><?php echo e(($record['leadership_milestones'] ?? '') !== '' ? $record['leadership_milestones'] : 'Your leadership milestone summary has not been recorded yet.'); ?></strong></p></div><div class="profile-actions"><a class="button primary" href="#app-progress">View Leadership Journey</a><a class="button ghost" href="#app-achievements">View Achievements</a></div></article>

    <div class="profile-two-grid profile-security-grid">
      <article class="profile-card"><div class="profile-card-heading"><span class="profile-card-icon profile-safety-icon" aria-hidden="true"></span><div><h2>Safety &amp; Agreements</h2><p>Your participation protections.</p></div></div><div class="profile-status-list"><p><span>Code of Conduct</span><strong><?php echo e($profileValue($student, 'Code of Conduct Agreement', 'Not recorded')); ?></strong></p><p><span>Recording Agreement</span><strong><?php echo e($profileValue($student, 'Recording Agreement', 'Not recorded')); ?></strong></p><p><span>Parent Permission</span><strong><?php echo e($profileValue($student, 'Parent Permission', 'Not recorded')); ?></strong></p><p><span>Adult Moderation</span><strong>Required for YUVA Club participation</strong></p></div></article>

      <article class="profile-card profile-account-card"><div class="profile-card-heading"><span class="profile-card-icon profile-account-icon" aria-hidden="true"></span><div><h2>Account</h2><p>Account access and help.</p></div></div><div class="profile-detail-list"><p><span>YUVA Club ID</span><strong><?php echo e($studentId); ?></strong></p><p><span>Account Help</span><strong>Password management is not available in the student app yet. Contact the YUVA Club team if you need account help.</strong></p></div><a class="button ghost profile-logout-button" href="portal-logout.php">Log Out</a></article>
    </div>

    <div class="profile-section-heading profile-future-heading"><p class="eyebrow">Future Profile</p><h2>More ways to make it yours</h2><p>These profile capabilities are planned for future updates.</p></div>
    <div class="profile-roadmap-grid"><article class="profile-roadmap"><span class="profile-roadmap-icon" aria-hidden="true"></span><div><h3>Profile Photo</h3><p>Add a safely managed profile photo in a future update.</p></div><strong>Future Update</strong></article><article class="profile-roadmap"><span class="profile-roadmap-icon" aria-hidden="true"></span><div><h3>Personal Goals</h3><p>Create and track personal growth goals.</p></div><strong>Coming Soon</strong></article><article class="profile-roadmap"><span class="profile-roadmap-icon" aria-hidden="true"></span><div><h3>Notification Preferences</h3><p>Choose future reminder and update preferences.</p></div><strong>Future Update</strong></article></div>
  </section>

  <section class="band">
    <div class="portal-module-grid">
      <?php foreach (rank_definitions() as $rankName => $rankInfo): ?>
        <div class="feature portal-module"><strong><?php echo e($rankName); ?></strong><p><?php echo e($rankInfo['meaning']); ?>. <?php echo e($rankInfo['requirements']); ?></p></div>
      <?php endforeach; ?>
      <a class="feature portal-module" href="#topic-selection"><strong>Choose Topic</strong><p>Select your category, title, date, and time.</p></a>
      <a class="feature portal-module" href="#research-submission"><strong>Submit Research</strong><p>Send notes, sources, outline, questions, and slides.</p></a>
      <a class="feature portal-module" href="#app-present"><strong>My Presentations</strong><p>Review selected and completed topics.</p></a>
      <div class="feature portal-module"><strong>Leadership Hours</strong><p><?php echo e($record['service_hours'] ?? '0'); ?> approved hours.</p></div>
      <div class="feature portal-module"><strong>Points & Tokens</strong><p><?php echo e((string) $points); ?> points and <?php echo e((string) $tokens); ?> tokens.</p></div>
      <div class="feature portal-module"><strong>Rewards</strong><p><?php echo e($record['reward_status'] ?? $rewardLevel); ?></p></div>
      <?php if ($certificateReady): ?><a class="feature portal-module" href="certificate.php?id=<?php echo e($studentId); ?>"><strong>Certificates</strong><p><?php echo e($certificateStatus); ?></p></a><?php else: ?><a class="feature portal-module" href="#app-achievements"><strong>Certificates</strong><p><?php echo e($certificateStatus); ?> — view status</p></a><?php endif; ?>
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

  <section class="band app-section" id="app-practice" data-app-section="practice">
    <?php
      $practiceTopicTitle = trim((string) ($selection['topic_title'] ?? ''));
      $practiceTopicSlug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $practiceTopicTitle), '-'));
      $practiceTopicAliases = ['a-p-j-abdul-kalam' => 'apj-abdul-kalam'];
      $practiceTopicSlug = $practiceTopicAliases[$practiceTopicSlug] ?? $practiceTopicSlug;
      $practiceTopicImage = $practiceTopicSlug !== '' ? 'assets/topics/' . $practiceTopicSlug . '.png' : '';
      if ($practiceTopicImage === '' || !is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $practiceTopicImage))) {
          $practiceTopicImage = '';
      }
      $practiceResearchStatus = $research['status'] ?? 'Not submitted yet';
    ?>
    <div class="practice-hero">
      <div>
        <p class="eyebrow">Practice</p>
        <h1>Build your speaking skills</h1>
        <p>Choose a topic, organize your research, and prepare with confidence.</p>
      </div>
      <div class="practice-hero-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </div>
    </div>

    <div class="practice-continue-card">
      <div class="practice-topic-art">
        <?php if ($practiceTopicImage !== ''): ?>
          <img src="<?php echo e($practiceTopicImage); ?>" alt="">
        <?php else: ?>
          <svg viewBox="0 0 64 64" aria-hidden="true"><path d="M10 14c12-3 20 0 22 7v33c-5-6-12-8-22-6V14Zm44 0c-12-3-20 0-22 7v33c5-6 12-8 22-6V14Z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="m38 12 7-7 7 7-7 7-7-7Z" fill="currentColor" opacity=".55"/></svg>
        <?php endif; ?>
      </div>
      <div class="practice-continue-copy">
        <p class="eyebrow">Continue Practice</p>
        <?php if ($selection): ?>
          <h2><?php echo e($selection['topic_title']); ?></h2>
          <p><?php echo e($selection['topic_category']); ?></p>
          <div class="practice-status-row"><span>Research</span><strong><?php echo e($practiceResearchStatus); ?></strong></div>
        <?php else: ?>
          <h2>Choose your first topic</h2>
          <p>Select a subject you care about and begin building your presentation.</p>
        <?php endif; ?>
      </div>
      <a class="button primary practice-continue-button" href="<?php echo $selection ? '#research-submission' : '#topic-selection'; ?>"><?php echo $selection ? 'Continue' : 'Choose a Topic'; ?></a>
    </div>

    <div class="practice-section-heading">
      <p class="eyebrow">Practice Tools</p>
      <h2>Your preparation workspace</h2>
    </div>

    <span class="app-anchor" id="topic-selection" aria-hidden="true"></span>
    <div class="practice-primary-grid">
      <form class="form-card practice-workspace-card practice-topic-card" action="portal-submit-topic.php" method="post">
        <?php echo csrf_field(); ?>
        <div class="practice-card-heading">
          <span class="practice-tool-icon practice-tool-topic" aria-hidden="true"></span>
          <div><p class="eyebrow">Topic</p><h2>Topic Selection</h2><p>Choose the subject for your next presentation.</p></div>
        </div>
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

      <div class="form-card practice-workspace-card practice-selection-summary">
        <div class="practice-card-heading">
          <span class="practice-tool-icon practice-tool-presentation" aria-hidden="true"></span>
          <div><p class="eyebrow">My Topic</p><h2 id="presentations">Current Presentation</h2><p>Your saved presentation plan.</p></div>
        </div>
        <?php if ($selection): ?>
          <div class="practice-summary-list">
            <p><span>Category</span><strong><?php echo e($selection['topic_category']); ?></strong></p>
            <p><span>Title</span><strong><?php echo e($selection['topic_title']); ?></strong></p>
            <p><span>Date</span><strong><?php echo e($selection['presentation_date']); ?> at <?php echo e($selection['presentation_time']); ?></strong></p>
            <p><span>Status</span><strong><?php echo e($selection['status'] ?? 'Pending Admin Review'); ?></strong></p>
          </div>
        <?php else: ?>
          <div class="practice-empty-state"><strong>No topic selected yet</strong><p>Your saved topic and presentation date will appear here.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <span class="app-anchor" id="research-submission" aria-hidden="true"></span>
    <form class="form-card practice-workspace-card practice-research-card" action="portal-submit-research.php" method="post" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <div class="practice-card-heading">
        <span class="practice-tool-icon practice-tool-research" aria-hidden="true"></span>
        <div><p class="eyebrow">Research</p><h2>Research Submission</h2><p>Organize your notes, sources, outline, questions, and supporting files.</p></div>
      </div>
      <div class="practice-research-grid">
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
      </div>
      <div class="practice-upload-row">
        <div class="field">
          <label for="research_file">Upload File or Slides</label>
          <input id="research_file" name="research_file" type="file" accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png">
        </div>
        <?php if (!empty($research['file_original'])): ?>
          <p class="practice-current-upload"><span>Current upload</span><a href="portal-download.php?id=<?php echo e($studentId); ?>"><?php echo e($research['file_original']); ?></a></p>
        <?php endif; ?>
      </div>
      <div class="practice-submit-row"><span>Research status: <strong><?php echo e($practiceResearchStatus); ?></strong></span><button class="button primary" type="submit">Submit Research</button></div>
    </form>

    <div class="practice-section-heading practice-coming-heading">
      <p class="eyebrow">On the roadmap</p>
      <h2>More ways to practice</h2>
      <p>These tools are planned for future YUVA Club releases.</p>
    </div>
    <div class="practice-future-grid">
      <a class="practice-future-card practice-future-ai practice-studio-link" href="#app-ai-coach"><span class="practice-tool-icon" aria-hidden="true"></span><div><h3>AI Coach Studio</h3><p>Open your student-safe coaching review and preparation feedback.</p></div><strong>Open Studio</strong></a>
      <article class="practice-future-card practice-future-video"><span class="practice-tool-icon" aria-hidden="true"></span><div><h3>Record Video</h3><p>Record and review your presentation practice.</p></div><strong>Coming Soon</strong></article>
      <article class="practice-future-card practice-future-timer"><span class="practice-tool-icon" aria-hidden="true"></span><div><h3>Speech Timer</h3><p>Practice pacing and timing for your presentation.</p></div><strong>Coming Soon</strong></article>
      <article class="practice-future-card practice-future-history"><span class="practice-tool-icon" aria-hidden="true"></span><div><h3>Practice History</h3><p>Review earlier practice sessions and growth.</p></div><strong>Coming Soon</strong></article>
    </div>
  </section>

  <section class="band" id="safety-report">
    <form class="form-card" action="portal-report-issue.php" method="post">
      <?php echo csrf_field(); ?>
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
<?php portal_footer(true); ?>
