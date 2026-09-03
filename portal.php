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
$publicIdentity = public_student_identity($studentId);
$publicAvatar = \YuvaClub\Identity\PublicStudentIdentity::avatar($publicIdentity['avatar_code']);
$leadershipProgress = null;
$leadershipHistory = [];
$completedPresentationSubmissions = [];
try {
    $leadershipProgress = leadership_eligibility_service()->latestByYuvaId($studentId);
    $leadershipHistory = leadership_eligibility_service()->history($studentId);
    $completedPresentationSubmissions = presentation_verification_service()->submissionsForStudent($studentId);
} catch (Throwable $error) {
    error_log('YUVA leadership student view unavailable correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
}
$leadershipNotice=(string)($_SESSION['leadership_notice']??'');$leadershipError=(string)($_SESSION['leadership_error']??'');unset($_SESSION['leadership_notice'],$_SESSION['leadership_error']);
$availableChallenges=[];$challengeEntries=[];
try{$availableChallenges=competition_foundation_service()->availableForStudent($studentId);$challengeEntries=competition_foundation_service()->entriesForStudent($studentId);}catch(Throwable $error){error_log('YUVA student challenges unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
$quickChallenges=[];try{$quickChallenges=quick_challenge_service()->studentChallenges($studentId);}catch(Throwable $error){error_log('YUVA student quick challenges unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
$quickChallengeAttempt=is_array($_SESSION['quick_challenge_attempt']??null)?$_SESSION['quick_challenge_attempt']:null;
$quickChallengeSubmittedAttempt=(string)($_SESSION['quick_challenge_submitted_attempt']??'');
$quickChallengeResults=[];try{$quickChallengeResults=quick_challenge_evaluation_service()->resultsForStudent($studentId);}catch(Throwable $error){error_log('YUVA student Quick Challenge results unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
$growthProfile=null;try{$growthProfile=growth_profile_service()->forStudent($studentId);}catch(Throwable $error){error_log('YUVA student My Growth unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
$competitionStudentNotice=(string)($_SESSION['competition_student_notice']??'');$competitionStudentError=(string)($_SESSION['competition_student_error']??'');unset($_SESSION['competition_student_notice'],$_SESSION['competition_student_error']);
$subscriptionStatus=['plan_code'=>'free','display_name'=>'Free','source_type'=>'default','ends_at'=>null];try{$subscriptionStatus=subscription_entitlement_service()->resolve($studentId);}catch(Throwable $error){error_log('YUVA student subscription view unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
$subscriptionStudentNotice=(string)($_SESSION['subscription_student_notice']??'');$subscriptionStudentError=(string)($_SESSION['subscription_student_error']??'');unset($_SESSION['subscription_student_notice'],$_SESSION['subscription_student_error']);
$publicIdentityError = (string)($_SESSION['public_identity_error'] ?? '');
unset($_SESSION['public_identity_error']);
$organizationMemberships = [];
try {
    $organizationMemberships = organization_membership_service()->requestsForStudent($studentId);
} catch (Throwable $error) {
    error_log('YUVA student organization membership view unavailable correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
}
$rewardLevel = reward_level($record);
$challengeStage = challenge_stage($record);
$rubricScore = rubric_score($record);
$rubricCompleted = rubric_completed_count($record);
$certificateStatus = $record['certificate_status'] ?? 'Not Ready';
$certificateReady = in_array($certificateStatus, ['Ready', 'Issued'], true);
$aiReviewRecord = ai_reviews()[$studentId] ?? [];
$aiReviewApproved = ($aiReviewRecord['status'] ?? '') === 'Applied';
$approvedAiReview = $aiReviewApproved && is_array($aiReviewRecord['review'] ?? null) ? $aiReviewRecord['review'] : [];
$aiReviewIncludedDocument = $aiReviewApproved
    && ($aiReviewRecord['document_analysis_status'] ?? '') === 'Analyzed';
$aiReviewState = ai_review_state($research !== null, $aiReviewRecord);
$aiReviewDate = $aiReviewApproved ? ($aiReviewRecord['applied_at'] ?? $aiReviewRecord['reviewed_at'] ?? '') : '';
$aiMentorNameParts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$aiMentorFirstName = $aiMentorNameParts[0] ?? $name;
$aiMentorTopic = trim((string) (
    $aiReviewRecord['topic_title']
    ?? $selection['topic_title']
    ?? ''
));
$aiMentorCategory = trim((string) (
    $aiReviewRecord['topic_category']
    ?? $selection['topic_category']
    ?? ''
));
$aiMentorHasActivePresentation = $aiMentorTopic !== '';
$aiMentorImprovements = !empty($approvedAiReview['improvements'])
    && is_array($approvedAiReview['improvements'])
    ? array_values(array_filter(
        $approvedAiReview['improvements'],
        static fn($item): bool => trim((string) $item) !== ''
    ))
    : [];
$aiMentorStrengths = !empty($approvedAiReview['strengths'])
    && is_array($approvedAiReview['strengths'])
    ? array_values(array_filter(
        $approvedAiReview['strengths'],
        static fn($item): bool => trim((string) $item) !== ''
    ))
    : [];
$aiMentorTodayFocus = $aiReviewApproved && $aiMentorImprovements !== []
    ? (string) $aiMentorImprovements[0]
    : '';
$aiMentorSummary = trim((string) ($approvedAiReview['summary'] ?? ''));
$aiMentorCommunicationNote = trim((string) ($approvedAiReview['communication_skills'] ?? ''));
$aiMentorLeadershipNote = trim((string) ($approvedAiReview['leadership_milestones'] ?? ''));
$aiMentorRecommendedNextStep = trim((string) ($approvedAiReview['recommended_next_step'] ?? ''));
$aiMentorHasValidTotal = array_key_exists('total_points', $approvedAiReview)
    && is_numeric($approvedAiReview['total_points'])
    && (int) $approvedAiReview['total_points'] >= 0
    && (int) $approvedAiReview['total_points'] <= 100;
$aiMentorTotal = $aiMentorHasValidTotal ? (int) $approvedAiReview['total_points'] : null;
$aiMentorHasSuggestedTokens = array_key_exists('suggested_tokens', $approvedAiReview)
    && is_numeric($approvedAiReview['suggested_tokens'])
    && (int) $approvedAiReview['suggested_tokens'] >= 0;
$aiMentorSuggestedTokens = $aiMentorHasSuggestedTokens
    ? (int) $approvedAiReview['suggested_tokens']
    : null;
$aiMentorCoachMeEnabled = ai_mentor_feature_enabled('coach_me_enabled');
$aiMentorMediaEnabled = ai_mentor_feature_enabled('media_analysis_enabled');
$presentationMedia = read_json_file(presentation_media_file())[$studentId] ?? [];
$presentationMediaActive = is_array($presentationMedia) && ($presentationMedia['retention_status'] ?? 'Active') === 'Active';
$mediaConsent = ['ready'=>false,'student_granted'=>false,'parent_required'=>true,'parent_granted'=>false,'version'=>\YuvaClub\Delivery\MediaConsentService::VERSION];
if ($aiMentorMediaEnabled && database_settings_present() && db_is_sqlsrv()) {
    try { $mediaConsent = media_consent_service()->status($studentId); } catch (Throwable) { $mediaConsent['ready'] = false; }
}
$deliveryReview = [];
if ($aiMentorMediaEnabled && database_settings_present() && db_is_sqlsrv()) {
    try { $deliveryReview = delivery_review_repository()->findLatest($studentId, true); } catch (Throwable) { $deliveryReview = []; }
}
$approvedDelivery = is_array($deliveryReview['review'] ?? null) ? $deliveryReview['review'] : [];
$submissionUploadOutcome = match ($status) {
    'upload-error', 'upload-unsupported' => 'unsupported',
    'upload-mismatch' => 'type-mismatch',
    'upload-too-large' => 'too-large',
    'upload-failed' => 'upload-failed',
    default => '',
};
$submissionState = \YuvaClub\Submission\ResearchSubmissionState::derive(
    is_array($research) ? $research : [],
    is_array($aiReviewRecord) ? $aiReviewRecord : [],
    $status === 'research-saved',
    $submissionUploadOutcome
);
$submissionPresentation = \YuvaClub\Submission\ResearchSubmissionState::presentation($submissionState);
$submissionIsError = in_array(
    $submissionState,
    [
        \YuvaClub\Submission\ResearchSubmissionState::UNSUPPORTED_FILE,
        \YuvaClub\Submission\ResearchSubmissionState::UPLOAD_FAILURE,
    ],
    true
);
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
$studentAnnouncements = text_lines($hub['announcements']);
$studentNotifications = [];
$notificationSessionTitle = $hasStudentZoom
    ? ($studentSessionTitle ?: 'YUVA Club session')
    : trim((string) ($session['title'] ?? ''));
$notificationSessionDate = $hasStudentZoom
    ? $studentSessionDate
    : trim((string) ($session['date'] ?? ''));
if ($notificationSessionDate !== '') {
    $studentNotifications[] = [
        'type' => 'session',
        'eyebrow' => 'Upcoming session',
        'title' => $notificationSessionTitle !== '' ? $notificationSessionTitle : 'Your next YUVA Club session',
        'body' => 'Scheduled for ' . $notificationSessionDate . '. Open Presentation Studio for the current session details.',
        'href' => '#app-present',
        'action' => 'View session',
    ];
}
if ($aiReviewState === 'approved') {
    $studentNotifications[] = [
        'type' => 'mentor',
        'eyebrow' => 'AI Mentor',
        'title' => 'Your approved guidance is ready',
        'body' => $aiMentorSummary !== '' ? $aiMentorSummary : 'Your administrator-approved review is ready to read.',
        'href' => '#app-ai-coach',
        'action' => 'Read guidance',
    ];
} elseif ($aiReviewState === 'awaiting-approval') {
    $studentNotifications[] = [
        'type' => 'pending',
        'eyebrow' => 'Review status',
        'title' => 'Your guidance is awaiting approval',
        'body' => 'Your latest review remains private while a YUVA Club administrator checks it.',
        'href' => '#app-ai-coach',
        'action' => 'View status',
    ];
} elseif ($research !== null) {
    $studentNotifications[] = [
        'type' => 'submission',
        'eyebrow' => 'Preparation status',
        'title' => (string) $submissionPresentation['title'],
        'body' => (string) $submissionPresentation['body'],
        'href' => '#research-submission',
        'action' => 'View preparation',
    ];
}
if ($certificateReady) {
    $studentNotifications[] = [
        'type' => 'recognition',
        'eyebrow' => 'Achievement',
        'title' => 'Your certificate is ' . strtolower($certificateStatus),
        'body' => 'Your approved certificate is available from Achievements.',
        'href' => '#app-achievements',
        'action' => 'View certificate',
    ];
}
foreach ($studentAnnouncements as $announcement) {
    $studentNotifications[] = [
        'type' => 'announcement',
        'eyebrow' => 'Club announcement',
        'title' => 'Update from YUVA Club',
        'body' => $announcement,
        'href' => '#announcements',
        'action' => 'View announcement',
    ];
}

portal_header('Student Dashboard', true);
?>
<main id="app-main" tabindex="-1">
  <section class="band"><div class="form-card"><p class="eyebrow">My Growth</p><h2>See what your practice is building</h2><p><?php echo is_array($growthProfile)?e((string)$growthProfile['next_action']['text']):'Your private growth profile will be available after the next platform migration.'; ?></p><a class="button primary" href="my-growth.php">Open My Growth</a></div></section>
  <section class="band app-section" id="app-home" data-app-section="home">
    <?php
      $nameParts = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
      $studentInitials = $publicAvatar['icon'];
      $homeSessionTitle = $hasStudentZoom ? ($studentSessionTitle ?: 'Yuva Club Session') : ($session['title'] ?? 'Yuva Club Session');
      $homeSessionDate = $hasStudentZoom ? $studentSessionDate : ($session['date'] ?? '');
      $homeSessionStart = $hasStudentZoom ? $studentSessionStart : ($session['start'] ?? '');
      $homeSessionEnd = $hasStudentZoom ? $studentSessionEnd : ($session['end'] ?? '');
      $homeSessionStatus = $hasStudentZoom ? $studentSessionStatus : ($session['status'] ?? 'Not scheduled');
      $homeAnnouncements = $studentAnnouncements;
      $homeRecentBadge = $badges ? (string) end($badges) : '';
      $homeMentorMessage = match ($aiReviewState) {
          'approved' => (string) ($approvedAiReview['summary'] ?? 'Your approved guidance is ready.'),
          'awaiting-approval' => 'Your latest guidance is being reviewed by a YUVA Club administrator.',
          'no-research' => 'Complete your research workspace when you are ready for guided feedback.',
          'unavailable' => 'Guidance is temporarily unavailable. Keep preparing and check back later.',
          default => 'Build your preparation one thoughtful step at a time.',
      };
    ?>
    <div class="home-welcome ds-story-hero">
      <div class="home-welcome-copy">
        <p class="home-greeting">Welcome back</p>
        <h1><?php echo e($name); ?>!</h1>
        <p>Lead with confidence. Grow with purpose. Make today count.</p>
      </div>
      <div class="home-welcome-actions">
        <a class="home-notification" href="#app-notifications" aria-label="Open student notifications">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?php if ($studentNotifications): ?><span class="home-notification-dot" aria-hidden="true"></span><?php endif; ?>
        </a>
        <a class="home-avatar" href="#app-profile" aria-label="Open YUVA Identity profile"><?php echo e($studentInitials); ?></a>
      </div>
      <img class="home-welcome-art" src="assets/student-hero-illustration.svg" alt="" aria-hidden="true">
      <span class="home-spark home-spark-one" aria-hidden="true"></span>
      <span class="home-spark home-spark-two" aria-hidden="true"></span>
      <span class="home-spark home-spark-three" aria-hidden="true"></span>
    </div>

    <?php if ($status === 'topic-saved'): ?><div class="form-status success" role="status" aria-live="polite">Topic selection saved.</div><?php endif; ?>
    <?php if ($status === 'topic-taken'): ?><div class="form-status error" role="alert">This topic is already selected by another student. Please choose a different topic.</div><?php endif; ?>
    <?php if ($status === 'research-saved'): ?><div class="form-status success" role="status" aria-live="polite">Research submission saved.</div><?php endif; ?>
    <?php if ($status === 'upload-error' || $status === 'upload-unsupported'): ?><div class="form-status error" role="alert">Your written preparation was saved, but that file format is not supported. Choose a PDF, PowerPoint, Word, JPG, or PNG file and try again.</div><?php endif; ?>
    <?php if ($status === 'upload-mismatch'): ?><div class="form-status error" role="alert">Your written preparation was saved, but the file content did not match its filename. Choose the original supported file and try again.</div><?php endif; ?>
    <?php if ($status === 'upload-too-large'): ?><div class="form-status error" role="alert">Your written preparation was saved, but the file was larger than 10 MB. Choose a smaller file and try again.</div><?php endif; ?>
    <?php if ($status === 'upload-failed'): ?><div class="form-status error" role="alert">Your written preparation was saved, but the file could not be uploaded. Choose the file again and retry.</div><?php endif; ?>
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
        <p class="eyebrow">Today’s Mission</p>
        <h2><?php echo e($nextAction['title']); ?></h2>
        <p><?php echo e($nextAction['body']); ?></p>
        <a class="button primary" href="<?php echo e($nextAction['href']); ?>"><?php echo e($nextAction['button']); ?></a>
      </div>

      <div class="form-card home-quick-access">
        <p class="eyebrow">Continue Practice</p>
        <h2>Practice Studio</h2>
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
        <p class="eyebrow">Leadership Journey</p>
        <h2><?php echo e($challengeStage); ?></h2>
        <div class="home-progress-list">
          <p><span>Presentations</span><strong><?php echo e($record['presentations'] ?? '0'); ?></strong></p>
          <p><span>Attendance</span><strong><?php echo e($record['attendance'] ?? '0'); ?> sessions</strong></p>
          <p><span>Service hours</span><strong><?php echo e($record['service_hours'] ?? '0'); ?> hours</strong></p>
          <p><span>Rubric score</span><strong><?php echo e((string) $rubricScore); ?> / 100</strong></p>
        </div>
        <a class="button ghost" href="#app-progress">View your journey</a>
      </div>

      <div class="form-card home-mentor-card">
        <span class="home-mentor-mark" aria-hidden="true"><?php echo student_app_icon('sparkles'); ?></span>
        <div>
          <p class="eyebrow">AI Mentor</p>
          <h2><?php echo $aiReviewState === 'approved' ? 'Your approved guidance' : 'A thoughtful next step'; ?></h2>
          <p><?php echo e($homeMentorMessage); ?></p>
          <a class="button ghost" href="#app-ai-coach"><?php echo $aiReviewState === 'approved' ? 'Read approved guidance' : 'View mentor status'; ?></a>
        </div>
      </div>

      <div class="form-card home-achievement-card">
        <span class="home-achievement-mark" aria-hidden="true"><?php echo student_app_icon('award'); ?></span>
        <div>
          <p class="eyebrow">Recent Achievement</p>
          <?php if ($homeRecentBadge !== ''): ?>
            <h2><?php echo e($homeRecentBadge); ?></h2>
            <p>This earned milestone is part of your real YUVA Club journey.</p>
          <?php elseif ($certificateReady): ?>
            <h2><?php echo e($certificateStatus); ?> certificate</h2>
            <p>Your approved certificate is ready to view.</p>
          <?php else: ?>
            <h2>Your first milestone is ahead</h2>
            <p>Real achievements will appear here as you complete approved activities.</p>
          <?php endif; ?>
          <a class="button ghost" href="#app-achievements">View achievements</a>
        </div>
      </div>

      <div class="form-card home-announcements home-card-wide">
        <p class="eyebrow">Announcements</p>
        <h2>Club updates</h2>
        <?php if ($homeAnnouncements): ?>
          <div class="home-announcement-list">
            <?php foreach ($homeAnnouncements as $line): ?><p><?php echo e($line); ?></p><?php endforeach; ?>
          </div>
        <?php else: ?>
          <p>You’re all caught up. Approved club announcements will appear here.</p>
        <?php endif; ?>
        <a class="button ghost" href="#app-notifications">View notifications</a>
      </div>
    </div>
  </section>

  <section class="band app-section" id="app-notifications" data-app-section="home" aria-labelledby="notifications-title">
    <header class="notifications-hero ds-story-hero">
      <div>
        <p class="eyebrow">Student Notifications</p>
        <h1 id="notifications-title">Stay connected to your journey.</h1>
        <p>Important updates from your real YUVA Club activity appear here—without noise or invented alerts.</p>
      </div>
      <span class="notifications-hero-mark" aria-hidden="true"><?php echo student_app_icon('bell'); ?></span>
    </header>

    <div class="notifications-summary" role="status" aria-live="polite">
      <div>
        <span>Current updates</span>
        <strong><?php echo e((string) count($studentNotifications)); ?></strong>
      </div>
      <p>Release 1.0 does not track unread status. This count reflects the updates currently available from your account data.</p>
    </div>

    <?php if ($studentNotifications): ?>
      <div class="notifications-list" aria-label="Current student notifications">
        <?php foreach ($studentNotifications as $notification): ?>
          <article class="notification-card notification-<?php echo e($notification['type']); ?>">
            <span class="notification-card-mark" aria-hidden="true"></span>
            <div class="notification-card-copy">
              <p class="eyebrow"><?php echo e($notification['eyebrow']); ?></p>
              <h2><?php echo e($notification['title']); ?></h2>
              <p><?php echo e($notification['body']); ?></p>
            </div>
            <a class="button ghost" href="<?php echo e($notification['href']); ?>"><?php echo e($notification['action']); ?></a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="notifications-empty" role="status">
        <span class="notifications-empty-mark" aria-hidden="true"><?php echo student_app_icon('bell'); ?></span>
        <div>
          <p class="eyebrow">All caught up</p>
          <h2>No current notifications</h2>
          <p>Session, approved review, certificate, and club updates will appear here when real information is available.</p>
        </div>
        <a class="button primary" href="#app-home">Return home</a>
      </div>
    <?php endif; ?>

    <aside class="notifications-privacy-note">
      <strong>Private to your student account</strong>
      <p>Notifications do not display parent contact details, private administrator notes, credentials, or internal organization data.</p>
    </aside>
  </section>

  <section class="band app-section" id="app-progress" data-app-section="progress">
    <?php
      $rankDefinitions = rank_definitions();
      $currentRankInfo = $rankDefinitions[$level] ?? $rankDefinitions['Explorer'];
      $nextRankInfo = $rankDefinitions[$nextRank] ?? $currentRankInfo;
      $certificateTitle = $currentRankInfo['certificate'] ?? 'Certificate of Participation';
    ?>
    <div class="journey-hero ds-story-hero">
      <div class="journey-hero-copy"><p class="eyebrow">Leadership Journey</p><h1>Your leadership story is unfolding.</h1><p>Every presentation, act of service, and brave new step becomes part of the leader you are growing into.</p><div class="journey-hero-status"><span>Leadership Level<strong><?php echo e($level); ?></strong></span><span>Current Chapter<strong><?php echo e($challengeStage); ?></strong></span></div></div>
      <img src="assets/student-leadership-journey-illustration.svg" alt="" aria-hidden="true">
    </div>

    <div class="journey-story">
    <div class="journey-section-heading journey-story-heading"><span class="journey-chapter-index" aria-hidden="true">01</span><div><p class="eyebrow">Your Journey So Far</p><h2>Real steps. Meaningful growth.</h2><p>These milestones come directly from your approved YUVA Club activity.</p></div></div>
    <div class="journey-metrics" aria-label="Student growth metrics">
      <article class="journey-metric journey-metric-points"><span class="journey-metric-icon" aria-hidden="true"></span><p>Points</p><strong><?php echo e((string) $points); ?></strong></article>
      <article class="journey-metric journey-metric-tokens"><span class="journey-metric-icon" aria-hidden="true"></span><p>Tokens</p><strong><?php echo e((string) $tokens); ?></strong></article>
      <article class="journey-metric journey-metric-attendance"><span class="journey-metric-icon" aria-hidden="true"></span><p>Attendance</p><strong><?php echo e($record['attendance'] ?? '0'); ?><small> sessions</small></strong></article>
      <article class="journey-metric journey-metric-hours"><span class="journey-metric-icon" aria-hidden="true"></span><p>Volunteer Hours</p><strong><?php echo e($record['service_hours'] ?? '0'); ?><small> hours</small></strong></article>
      <article class="journey-metric journey-metric-presentations"><span class="journey-metric-icon" aria-hidden="true"></span><p>Presentations</p><strong><?php echo e($record['presentations'] ?? '0'); ?></strong></article>
    </div>

    <div class="journey-primary-grid" aria-label="Current leadership chapter">
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

    <?php if (is_array($leadershipProgress)): ?>
    <section class="journey-card" aria-labelledby="leadership-progress-title">
      <p class="eyebrow">Your Leadership Journey</p><h2 id="leadership-progress-title"><?php echo e((string)$leadershipProgress['current_level']); ?> → <?php echo e((string)($leadershipProgress['target_level']??'Continued mentorship')); ?></h2>
      <p><strong><?php echo e((string)$leadershipProgress['status']); ?></strong> · <?php echo e((string)$leadershipProgress['completed']); ?> of <?php echo e((string)$leadershipProgress['required']); ?> requirements complete</p>
      <?php if($leadershipProgress['status']==='Eligible for Review'): ?><p>You’ve completed the current requirements for <?php echo e((string)$leadershipProgress['target_level']); ?>. Your progress is ready for human review.</p><?php endif; ?>
      <ul><?php foreach($leadershipProgress['requirements'] as $requirement): ?><li><?php echo !empty($requirement['complete'])?'✓':'○'; ?> <?php echo e((string)$requirement['label']); ?> (<?php echo e((string)$requirement['actual']); ?>/<?php echo e((string)$requirement['required']); ?>)</li><?php endforeach; ?></ul>
      <article class="form-card"><h3>Completed presentation verification</h3><p>Submit your current completed research presentation for review by an authorized human. This does not verify the presentation or change your level by itself.</p><?php if(is_array($research)&&$research!==[]): ?><form action="student-presentation-complete.php" method="post"><?php echo csrf_field(); ?><button class="button ghost" type="submit">Submit Completed Presentation for Verification</button></form><?php else: ?><p>Complete your research workspace before submitting a presentation.</p><?php endif; ?><?php if($completedPresentationSubmissions!==[]): ?><ul><?php foreach($completedPresentationSubmissions as $submission): ?><li>Presentation <?php echo e((string)$submission['id']); ?> · <?php echo e((string)($submission['verification_status']??'Awaiting Human Verification')); ?></li><?php endforeach; ?></ul><?php endif; ?></article>
      <?php if($leadershipNotice!==''): ?><div class="form-status success"><?php echo e($leadershipNotice); ?></div><?php endif; ?><?php if($leadershipError!==''): ?><div class="form-status error"><?php echo e($leadershipError); ?></div><?php endif; ?>
      <div class="dashboard-grid">
        <form action="student-leadership-reflection.php" method="post" class="form-card"><?php echo csrf_field(); ?><h3>Complete a reflection</h3><label>What went well?<textarea name="went_well" required maxlength="1500"></textarea></label><label>What would you improve next time?<textarea name="improve_next" required maxlength="1500"></textarea></label><label>What did you learn?<textarea name="learned" required maxlength="1500"></textarea></label><label>What is your next goal?<textarea name="next_goal" required maxlength="1500"></textarea></label><button class="button primary" type="submit">Save Reflection</button></form>
        <form action="student-leadership-contribution.php" method="post" class="form-card"><?php echo csrf_field(); ?><h3>Submit a contribution</h3><label>Contribution type<select name="evidence_type"><option value="leadership_service">Leadership or service</option><option value="peer_support">Peer support or mentoring</option></select></label><label>Activity title<input name="title" required maxlength="180"></label><label>Date<input type="date" name="evidence_date" required></label><label>Optional hours<input type="number" name="hours" min="0" max="1000" step="0.25"></label><label>What did you contribute or learn?<textarea name="description" required maxlength="1500"></textarea></label><button class="button primary" type="submit">Submit for Human Review</button></form>
      </div>
      <?php if($leadershipHistory!==[]): ?><h3>Approved level history</h3><ul><?php foreach($leadershipHistory as $history): ?><li><?php echo e((string)$history['previous_level']); ?> → <?php echo e((string)$history['new_level']); ?> · <?php echo e(display_eastern_time((string)$history['promoted_at'])); ?></li><?php endforeach; ?></ul><?php endif; ?>
    </section>
    <?php endif; ?>

    <div class="journey-section-heading journey-story-heading journey-challenge-heading"><span class="journey-chapter-index" aria-hidden="true">02</span><div><p class="eyebrow">Your Current Chapter</p><h2>Follow the challenge path</h2><p>Your approved challenge stage anchors this part of your journey.</p></div></div>
    <article class="journey-card journey-challenge-card">
      <div class="journey-card-heading"><span class="journey-card-icon journey-challenge-icon" aria-hidden="true"></span><div><p class="eyebrow">Challenge Journey</p><h2><?php echo e($challengeStage); ?></h2><p>The Global Youth Speaking &amp; Leadership Challenge</p></div></div>
      <div class="journey-challenge-meta"><p><span>Month</span><strong><?php echo e($record['challenge_month'] ?? date('Y-m')); ?></strong></p><p><span>Region</span><strong><?php echo e(($record['challenge_region'] ?? '') !== '' ? $record['challenge_region'] : 'Not assigned'); ?></strong></p><p><span>Finalist Status</span><strong><?php echo e($record['finalist_status'] ?? 'Not Qualified'); ?></strong></p><p><span>Award Status</span><strong><?php echo e($record['award_status'] ?? 'None'); ?></strong></p></div>
      <div class="journey-stage-path" aria-label="Challenge stages"><?php foreach (challenge_stages() as $stage): ?><div class="<?php echo $stage === $challengeStage ? 'is-current' : ''; ?>"><span aria-hidden="true"></span><strong><?php echo e($stage); ?></strong></div><?php endforeach; ?></div>
    </article>

    <div class="journey-section-heading journey-story-heading"><span class="journey-chapter-index" aria-hidden="true">03</span><div><p class="eyebrow">Milestones</p><h2>Recognition earned along the way</h2><p>Only badges earned through your real participation appear here.</p></div></div>
    <?php if ($badges): ?><div class="journey-badge-grid"><?php foreach ($badges as $index => $badge): ?><article class="journey-badge journey-badge-<?php echo e((string) (($index % 5) + 1)); ?>"><span aria-hidden="true"></span><strong><?php echo e($badge); ?></strong><small>Earned</small></article><?php endforeach; ?></div><?php else: ?><div class="journey-empty-state"><span class="journey-empty-icon" aria-hidden="true"></span><div><h2>Your first badge is waiting</h2><p>Your first badge will appear as you participate and complete real milestones.</p></div></div><?php endif; ?>

    <div class="journey-section-heading journey-story-heading journey-rubric-heading"><span class="journey-chapter-index" aria-hidden="true">04</span><div><p class="eyebrow">Reflection</p><h2>Learn from every presentation</h2><p>Your official evaluation and approved guidance shape the next step.</p></div></div>
    <div class="journey-rubric-layout">
      <article class="journey-card journey-rubric-card"><div class="journey-rubric-total"><span>Total Score</span><strong><?php echo e((string) $rubricScore); ?><small>/100</small></strong><p><?php echo e((string) $rubricCompleted); ?> of <?php echo e((string) count(rubric_categories())); ?> categories scored</p></div><div class="journey-rubric-list"><?php foreach (rubric_categories() as $rubricKey => $rubricLabel): ?><p><span><?php echo e($rubricLabel); ?></span><strong><?php echo ($record['rubric_' . $rubricKey] ?? '') !== '' ? e((string) $record['rubric_' . $rubricKey]) . ' / 10' : 'Not scored'; ?></strong></p><?php endforeach; ?></div></article>
      <article class="journey-card journey-feedback-card"><div class="journey-card-heading"><span class="journey-card-icon journey-feedback-icon" aria-hidden="true"></span><div><p class="eyebrow">Judge Feedback</p><h2>Guidance for your next step</h2></div></div><p><?php echo e(($record['judge_feedback'] ?? '') !== '' ? $record['judge_feedback'] : 'Challenge feedback will appear after a mentor or judge reviews your presentation.'); ?></p><div class="journey-award-status"><span>Award Status</span><strong><?php echo e($record['award_status'] ?? 'None'); ?></strong></div></article>
    </div>

    <article class="journey-leaderboard-card"><div><p class="eyebrow">Leadership Challenge</p><h2>See the challenge leaderboard</h2><p>Explore approved progress by program and challenge stage. Your position is calculated on the leaderboard page.</p></div><a class="button primary" href="leaderboard.php">View Leaderboard</a></article>

    <div class="journey-section-heading journey-future-heading"><p class="eyebrow">Future Growth</p><h2>More ways to build momentum</h2><p>These capabilities are planned for future YUVA Club updates.</p></div>
    <div class="journey-roadmap-grid"><article class="journey-roadmap journey-roadmap-goals"><span class="journey-roadmap-icon" aria-hidden="true"></span><div><h3>Weekly Goals</h3><p>Set and track weekly leadership activities.</p></div><strong>Coming Soon</strong></article><article class="journey-roadmap journey-roadmap-streak"><span class="journey-roadmap-icon" aria-hidden="true"></span><div><h3>Streak Tracking</h3><p>Celebrate consistent participation over time.</p></div><strong>Coming Soon</strong></article><article class="journey-roadmap journey-roadmap-rewards"><span class="journey-roadmap-icon" aria-hidden="true"></span><div><h3>Token Rewards</h3><p>Use tokens with future approved rewards.</p></div><strong>Future Update</strong></article></div>
    </div>
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
    <div class="achievements-hero ds-story-hero">
      <div class="achievements-hero-copy"><a class="achievements-back" href="#app-progress">← Leadership Journey</a><p class="eyebrow">Achievements</p><h1>Celebrate how far you've come.</h1><p>Your presentations, service, badges, and leadership growth tell the story of the leader you are becoming.</p><div class="achievements-hero-stats"><span>Leadership Rank<strong><?php echo e($level); ?></strong></span><span>Badges Earned<strong><?php echo e((string) count($badges)); ?></strong></span></div></div>
      <img src="assets/student-achievements-illustration.svg" alt="" aria-hidden="true">
    </div>

    <div class="achievements-section-heading ds-section-heading"><p class="eyebrow">Recognition Snapshot</p><h2>Recognition you have earned</h2><p>Every item here comes from your verified YUVA Club participation.</p></div>
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

    <div class="achievements-section-heading ds-section-heading"><p class="eyebrow">Earned Badges</p><h2>Milestones worth celebrating</h2><p>Only badges earned from your real YUVA Club progress appear here.</p></div>
    <?php if ($badges): ?><div class="achievements-badge-grid"><?php foreach ($badges as $index => $badge): ?><article class="achievements-badge achievements-badge-<?php echo e((string) (($index % 5) + 1)); ?>"><span class="achievements-badge-shield" aria-hidden="true"><i></i></span><h3><?php echo e($badge); ?></h3><strong>Earned</strong></article><?php endforeach; ?></div><?php else: ?><div class="achievements-empty"><span aria-hidden="true"></span><div><h2>Your first badge is waiting</h2><p>Your first badge will appear as you participate and complete real milestones.</p></div></div><?php endif; ?>

    <div class="achievements-two-grid">
      <article class="achievements-card achievements-recognition-card"><div class="achievements-card-heading"><span class="achievements-card-icon achievements-recognition-icon" aria-hidden="true"></span><div><p class="eyebrow">Leadership Recognition</p><h2>Your leadership story</h2></div></div><div class="achievements-detail-list"><p><span>Leadership Milestone</span><strong><?php echo e(($record['leadership_milestones'] ?? '') !== '' ? $record['leadership_milestones'] : 'Your leadership milestone summary has not been recorded yet.'); ?></strong></p><p><span>Rank Recommendation</span><strong><?php echo e(($record['rank_recommendation'] ?? '') !== '' ? $record['rank_recommendation'] : 'Your next-rank recommendation has not been reviewed yet.'); ?></strong></p><p><span>Challenge Stage</span><strong><?php echo e($challengeStage); ?></strong></p><p><span>Finalist Status</span><strong><?php echo e($record['finalist_status'] ?? 'Not Qualified'); ?></strong></p><p><span>Award Status</span><strong><?php echo e($record['award_status'] ?? 'None'); ?></strong></p></div></article>
      <article class="achievements-card achievements-evidence-card"><div class="achievements-card-heading"><span class="achievements-card-icon achievements-evidence-icon" aria-hidden="true"></span><div><p class="eyebrow">Growth Evidence</p><h2>Your current contribution</h2></div></div><div class="achievements-detail-list"><p><span>Presentations</span><strong><?php echo e($record['presentations'] ?? '0'); ?></strong></p><p><span>Volunteer/Leadership Hours</span><strong><?php echo e($record['service_hours'] ?? '0'); ?></strong></p><p><span>Sessions Attended</span><strong><?php echo e($record['attendance'] ?? '0'); ?></strong></p><p><span>Current Topic</span><strong><?php echo e($selection['topic_title'] ?? 'No topic selected yet'); ?></strong></p><p><span>Official Rubric</span><strong><?php echo e((string) $rubricScore); ?> / 100</strong></p></div></article>
    </div>

    <div class="achievements-section-heading ds-section-heading"><p class="eyebrow">Recognition Notes</p><h2>Encouragement from your community</h2><p>Approved words from the people supporting your leadership growth.</p></div>
    <?php if ($hasAchievementNotes): ?><div class="achievements-notes-grid"><?php foreach ($achievementNotes as $noteTitle => $noteText): ?><?php if ($noteText !== ''): ?><article class="achievements-note"><span aria-hidden="true"></span><h3><?php echo e($noteTitle); ?></h3><p><?php echo e($noteText); ?></p></article><?php endif; ?><?php endforeach; ?></div><?php else: ?><div class="achievements-empty achievements-notes-empty"><span aria-hidden="true"></span><div><h2>Recognition notes will appear here</h2><p>Recognition notes will appear after a mentor, teacher, or judge reviews your progress.</p></div></div><?php endif; ?>

    <div class="achievements-section-heading achievements-future-heading ds-section-heading"><p class="eyebrow">Future Portfolio</p><h2>More ways to preserve your journey</h2><p>These portfolio capabilities are planned for future YUVA Club updates.</p></div>
    <div class="achievements-roadmap-grid"><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Certificate History</h3><p>Review certificates earned over time.</p></div><strong>Coming Soon</strong></article><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Achievement Timeline</h3><p>See milestones in a future dated timeline.</p></div><strong>Coming Soon</strong></article><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Share Achievements</h3><p>Share approved recognition safely.</p></div><strong>Future Update</strong></article><article class="achievements-roadmap"><span class="achievements-roadmap-icon" aria-hidden="true"></span><div><h3>Download Portfolio</h3><p>Create a future portable achievement record.</p></div><strong>Future Update</strong></article></div>
  </section>

  <section class="band app-section" id="app-present" data-app-section="present">
    <div class="present-hero studio-hero studio-hero-present ds-workspace-hero">
      <div class="present-hero-copy studio-hero-copy"><p class="eyebrow">Presentation Studio</p><h1>Your voice inspires!</h1><p>Prepare your ideas, join your session, and lead the change.</p></div>
      <img src="assets/student-presentation-illustration.svg" alt="" aria-hidden="true">
    </div>

    <div class="present-center-grid">
      <section class="present-session-card present-card-wide studio-card studio-card-featured" aria-labelledby="present-upcoming-title">
        <div class="present-card-heading studio-card-heading"><span class="present-icon present-icon-calendar" aria-hidden="true"></span><div><p class="eyebrow">Upcoming Presentation</p><h2 id="present-upcoming-title"><?php echo e($hasStudentZoom ? ($studentSessionTitle ?: 'Yuva Club Session') : ($session['title'] ?? 'Yuva Club Session')); ?></h2></div></div>
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

      <article class="present-info-card present-topic-card studio-card"><span class="present-icon present-icon-topic" aria-hidden="true"></span><div><p class="eyebrow">Presentation Topic</p><?php if ($selection): ?><h2><?php echo e($selection['topic_title']); ?></h2><p><?php echo e($selection['topic_category']); ?></p><small><?php echo e($selection['presentation_date']); ?> at <?php echo e($selection['presentation_time']); ?> · <?php echo e($selection['status'] ?? 'Pending Admin Review'); ?></small><?php else: ?><h2>No topic selected yet</h2><p>Choose a topic in your Practice Workspace to prepare for your next presentation.</p><a href="#topic-selection">Choose a Topic</a><?php endif; ?></div></article>

      <article class="present-info-card present-readiness-card studio-card"><span class="present-icon present-icon-check" aria-hidden="true"></span><div><p class="eyebrow">Research Readiness</p><h2><?php echo e($research['status'] ?? 'Not Submitted'); ?></h2><p><?php echo $research ? 'Your saved preparation is ready to review.' : 'Build your notes, sources, outline, and questions in Practice Workspace.'; ?></p><a href="#research-submission">Open Research Workspace</a></div></article>

      <article class="present-info-card present-upload-card studio-card"><span class="present-icon present-icon-upload" aria-hidden="true"></span><div><p class="eyebrow">Upload Slides</p><h2><?php echo !empty($research['file_original']) ? e($research['file_original']) : 'No slides uploaded yet'; ?></h2><p>Upload or replace PDF, PowerPoint, document, or image files using the existing Research upload.</p><?php if (!empty($research['file_original'])): ?><a href="portal-download.php?id=<?php echo e($studentId); ?>">View current upload</a><?php endif; ?><a href="#research-submission">Upload or Replace Slides</a></div></article>

      <article class="present-info-card present-questions-card studio-card"><span class="present-icon present-icon-question" aria-hidden="true"></span><div><p class="eyebrow">Prepared Questions</p><?php if (!empty($research['prepared_questions'])): ?><h2>Questions ready</h2><p class="present-question-preview"><?php echo nl2br(e($research['prepared_questions'])); ?></p><?php else: ?><h2>No questions prepared yet</h2><p>Add possible audience questions and your answers in Practice Workspace.</p><?php endif; ?><a href="#research-submission">Review Questions</a></div></article>

      <section class="present-scheduler-card present-card-wide studio-card" aria-labelledby="present-schedule-title"><div class="present-card-heading studio-card-heading"><span class="present-icon present-icon-schedule" aria-hidden="true"></span><div><p class="eyebrow">Presentation Schedule</p><h2 id="present-schedule-title">Schedule your session</h2><p>Use the official YUVA Club Zoom Scheduler.</p></div></div>
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
      <?php if ($aiMentorMediaEnabled): ?>
      <form class="present-card-wide studio-card" action="portal-submit-media.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="consent_version" value="<?php echo e((string)$mediaConsent['version']); ?>">
        <p class="eyebrow">Presentation Delivery Review</p><h2>Upload a short practice presentation</h2>
        <p><strong>AI processing is enabled for this tool.</strong> Your recording is sent to OpenAI to create presentation coaching. Where supported, this may include transcription, pace, pauses, filler words, clarity, emphasis, and sampled presentation observations. AI can make mistakes. A YUVA Club administrator controls what feedback you see, and this review does not determine an official academic grade. Avoid including sensitive personal information you do not need to share.</p>
        <p>MP4, WebM, MP3, WAV, or M4A. Maximum 25 MB and 5 minutes. YUVA Club's automatic media deletion schedule is <?php echo media_retention_days()===null?'not yet enabled':e((string)media_retention_days()).' days'; ?>.</p>
        <?php if (!empty($mediaConsent['parent_required']) && empty($mediaConsent['parent_granted'])): ?>
          <p role="status"><strong>Parent/guardian permission is required.</strong> Ask your linked parent or guardian to approve media AI coaching in their dashboard before you upload.</p>
        <?php else: ?>
          <label for="presentation_media">Practice recording</label><input id="presentation_media" name="presentation_media" type="file" accept=".mp4,.webm,.mp3,.wav,.m4a" required>
          <label><input name="media_ai_acknowledgement" type="checkbox" value="yes" required> I understand that this recording will be processed by YUVA Club's AI service to provide presentation coaching.</label>
          <button class="button primary" type="submit">Submit Practice Recording</button>
        <?php endif; ?>
        <?php if ($presentationMediaActive && !empty($presentationMedia['original_filename'])): ?><p>Current recording: <?php echo e((string)$presentationMedia['original_filename']); ?> · Analysis status: <?php echo e((string)($presentationMedia['status']??'Pending')); ?></p><button class="button ghost" type="submit" formmethod="post" formaction="portal-delete-media.php" formnovalidate>Delete recording</button><p>Deleting removes the uploaded recording. Contact support to request deletion of retained transcripts or applied program records.</p><?php endif; ?>
      </form>
      <?php endif; ?>
      <div class="present-future-grid present-card-wide">
        <article class="present-future-card present-future-timer"><span class="present-icon" aria-hidden="true"></span><div><h3>Presentation Timer</h3><p>Practice pacing before your live session.</p></div><strong>Coming Soon</strong></article>
        <article class="present-future-card present-future-history"><span class="present-icon" aria-hidden="true"></span><div><h3>Past Presentations</h3><p>Review individual sessions and feedback.</p></div><strong>Coming Soon</strong></article>
        <article class="present-future-card present-future-replay"><span class="present-icon" aria-hidden="true"></span><div><h3>Replay</h3><p>Watch saved presentation recordings.</p></div><strong>Coming Soon</strong></article>
        <article class="present-future-card present-future-analytics"><span class="present-icon" aria-hidden="true"></span><div><h3>Presentation Analytics</h3><p>Explore delivery insights and growth trends.</p></div><strong>Future Update</strong></article>
      </div>
    </div>
  </section>

  <section class="band app-section" id="app-ai-coach" data-app-section="practice">
    <div class="ai-studio-hero ai-mentor-hero ds-story-hero">
      <div>
        <p class="eyebrow">AI Mentor</p>
        <h1>Hi, <?php echo e($aiMentorFirstName); ?>. Let’s grow your voice.</h1>
        <p>Your approved mentor guidance helps you notice your strengths, reflect with confidence, and take one thoughtful next step.</p>
      </div>
      <img src="assets/student-ai-coach-illustration.svg" alt="" aria-hidden="true">
    </div>

    <?php if ($approvedDelivery !== []): ?>
    <section class="ai-approved-review-section" aria-labelledby="ai-delivery-review-title">
      <div class="ai-studio-section-heading"><p class="eyebrow">Presentation Delivery Review</p><h2 id="ai-delivery-review-title">Your approved delivery coaching</h2><p>This AI Mentor coaching is advisory and separate from the official presentation rubric.</p></div>
      <article><p>Overall delivery score</p><strong><?php echo e((string)($approvedDelivery['overall_delivery_score']??0)); ?> / 100</strong><p><?php echo e((string)($approvedDelivery['summary']??'')); ?></p><span><?php echo e((string)($approvedDelivery['suggested_tokens']??0)); ?> approved tokens</span></article>
      <div class="ai-score-list">
      <?php foreach(['pace_score'=>'Pacing','pause_control_score'=>'Pause control','clarity_score'=>'Clarity','vocal_variety_score'=>'Vocal variety','emphasis_score'=>'Emphasis','filler_word_score'=>'Filler words','visual_presence_score'=>'Sampled visual presence'] as $field=>$label): ?><?php if(array_key_exists($field,$approvedDelivery)&&$approvedDelivery[$field]!==null): ?><div><span><?php echo e($label); ?></span><strong><?php echo e((string)$approvedDelivery[$field]); ?> / 100</strong></div><?php endif; ?><?php endforeach; ?>
      </div>
      <article><h3>Strengths</h3><ul><?php foreach(($approvedDelivery['strengths']??[]) as $item): ?><li><?php echo e((string)$item); ?></li><?php endforeach; ?></ul></article>
      <article><h3>Improvement priorities</h3><ul><?php foreach(($approvedDelivery['improvements']??[]) as $item): ?><li><?php echo e((string)$item); ?></li><?php endforeach; ?></ul></article>
      <article><h3>Delivery notes</h3><p><?php echo e((string)($approvedDelivery['pacing_feedback']??'')); ?></p><p><?php echo e((string)($approvedDelivery['pause_feedback']??'')); ?></p><p><?php echo e((string)($approvedDelivery['clarity_feedback']??'')); ?></p><p><?php echo e((string)($approvedDelivery['filler_word_feedback']??'')); ?></p><p><?php echo e((string)($approvedDelivery['visual_feedback']??'')); ?></p></article>
      <?php if(!empty($approvedDelivery['pronunciation_practice'])): ?><article><h3>Optional clarity practice</h3><ul><?php foreach($approvedDelivery['pronunciation_practice'] as $item): ?><li><?php echo e(is_array($item)?(string)($item['recommendation']??$item['word']??''):(string)$item); ?></li><?php endforeach; ?></ul></article><?php endif; ?>
      <article><h3>Time-coded coaching</h3><ol><?php foreach(($approvedDelivery['timecoded_coaching']??[]) as $moment): $seconds=max(0,(int)($moment['start_seconds']??0)); ?><li><strong><?php echo e(sprintf('%d:%02d',intdiv($seconds,60),$seconds%60)); ?></strong> — <?php echo e((string)($moment['observation']??'')); ?> <?php echo e((string)($moment['recommendation']??'')); ?></li><?php endforeach; ?></ol></article>
      <article><h3>Recommended next practice step</h3><p><?php echo e((string)($approvedDelivery['recommended_next_step']??'')); ?></p></article>
    </section>
    <?php endif; ?>

    <div class="ai-mentor-home-grid">
      <article class="ai-mentor-home-card ai-mentor-context-card">
        <p class="eyebrow"><?php echo $aiMentorHasActivePresentation ? 'Current Presentation' : 'Presentation Context'; ?></p>
        <?php if ($aiMentorHasActivePresentation): ?>
          <h2><?php echo e($aiMentorTopic); ?></h2>
          <?php if ($aiMentorCategory !== ''): ?><p><?php echo e($aiMentorCategory); ?></p><?php endif; ?>
          <a class="ai-mentor-text-link" href="#app-present">View in Presentation Studio</a>
        <?php else: ?>
          <h2>No active presentation yet</h2>
          <p>Choose a topic when you are ready to begin preparing your next presentation.</p>
          <a class="ai-mentor-text-link" href="#topic-selection">Choose a presentation topic</a>
        <?php endif; ?>
      </article>

      <article class="ai-mentor-home-card ai-mentor-focus-card">
        <p class="eyebrow">Today’s Focus</p>
        <?php if ($aiMentorTodayFocus !== ''): ?>
          <h2>One approved next step</h2>
          <p><?php echo e($aiMentorTodayFocus); ?></p>
        <?php else: ?>
          <h2>Your focus will appear here</h2>
          <p>Today’s focus is shown only when an approved review includes an improvement priority.</p>
        <?php endif; ?>
      </article>

      <article class="ai-mentor-home-card ai-mentor-action-card">
        <div>
          <p class="eyebrow">Guided Practice</p>
          <h2>Coach Me</h2>
          <p>Personalized coaching activities are not available in this version yet.</p>
        </div>
        <button
          class="button primary ai-mentor-coach-button"
          type="button"
          disabled
          aria-describedby="ai-mentor-coach-status"
        >Coach Me</button>
        <p id="ai-mentor-coach-status" class="ai-mentor-capability-status" role="status">
          <?php echo $aiMentorCoachMeEnabled
              ? 'Coach Me is enabled by configuration but is not connected in this milestone.'
              : 'Coach Me is coming in a future update.'; ?>
        </p>
      </article>
    </div>

    <article class="ai-submission-status ai-submission-status-<?php echo e($submissionPresentation['tone']); ?>" aria-labelledby="ai-submission-status-title">
      <span class="ai-submission-status-mark" aria-hidden="true"></span>
      <div <?php echo $submissionIsError ? 'role="alert"' : 'role="status" aria-live="polite"'; ?>>
        <p class="eyebrow"><?php echo e($submissionPresentation['eyebrow']); ?></p>
        <h2 id="ai-submission-status-title"><?php echo e($submissionPresentation['title']); ?></h2>
        <p><?php echo e($submissionPresentation['body']); ?></p>
      </div>
      <?php if ($submissionState === \YuvaClub\Submission\ResearchSubmissionState::REVIEW_APPROVED): ?>
        <a class="button ghost" href="#ai-research-review-title">Read Approved Review</a>
      <?php elseif ($submissionState === \YuvaClub\Submission\ResearchSubmissionState::NO_SUBMISSION): ?>
        <a class="button ghost" href="#research-submission">Prepare Submission</a>
      <?php elseif ($submissionState === \YuvaClub\Submission\ResearchSubmissionState::DRAFT_INCOMPLETE || $submissionState === \YuvaClub\Submission\ResearchSubmissionState::NEEDS_RESUBMISSION || $submissionIsError): ?>
        <a class="button ghost" href="#research-submission">Review and Resubmit</a>
      <?php endif; ?>
    </article>

    <?php if ($aiReviewState !== 'approved'): ?>
      <div class="ai-studio-state ai-mentor-state ai-state-<?php echo e($aiReviewState); ?>">
        <span class="ai-studio-state-icon" aria-hidden="true"></span>
        <div role="status" aria-live="polite">
          <?php if (!$aiMentorHasActivePresentation): ?><p class="eyebrow">No active presentation</p><h2>Choose a topic to begin</h2><p>AI Mentor uses your real presentation preparation and approved reviews. Start by choosing a topic in Presentation Studio.</p><a class="button primary" href="#topic-selection">Choose Topic</a>
          <?php elseif ($aiReviewState === 'no-research'): ?><p class="eyebrow">First time here</p><h2>Prepare something your mentor can review</h2><p>Submit your research notes, sources, outline, and questions. Guidance appears only after the review is approved by a YUVA Club administrator.</p><a class="button primary" href="#research-submission">Open Research Workspace</a>
          <?php elseif ($aiReviewState === 'not-created'): ?><p class="eyebrow">No review yet</p><h2>Your preparation is ready for the next step</h2><p>No mentor review has been created for this submission. Approved guidance will appear here after the current review workflow is completed.</p><a class="button ghost" href="#app-present">Return to Presentation Studio</a>
          <?php elseif ($aiReviewState === 'awaiting-approval'): ?><p class="eyebrow">Review pending</p><h2>Your guidance is being carefully reviewed</h2><p>A YUVA Club administrator is checking the review before it becomes visible. No completion time is promised.</p><a class="button ghost" href="#app-home">Return Home</a>
          <?php else: ?><p class="eyebrow">Mentor guidance</p><h2>Your guidance is temporarily unavailable</h2><p>Your AI Mentor review cannot be displayed right now. Please check back later.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="ai-studio-approved-banner" role="status">
        <span class="ai-approved-mark" aria-hidden="true"></span>
        <div><p class="eyebrow">Approved Review</p><strong>Visible after review by a YUVA Club administrator</strong></div>
        <small><?php echo $aiReviewDate !== '' ? e($aiReviewDate) : 'Approval date not recorded'; ?></small>
      </div>

      <article class="ai-mentor-latest-review" aria-labelledby="ai-mentor-latest-review-title">
        <div>
          <p class="eyebrow">Latest Approved Review</p>
          <h2 id="ai-mentor-latest-review-title">A note for your next step</h2>
          <p><?php echo e($aiMentorSummary !== '' ? $aiMentorSummary : 'No summary was included in this approved review.'); ?></p>
        </div>
        <div class="ai-mentor-review-highlights">
          <div><span>Recent strength</span><strong><?php echo e($aiMentorStrengths[0] ?? 'No strength was included in this approved review.'); ?></strong></div>
          <div><span>Improvement priority</span><strong><?php echo e($aiMentorImprovements[0] ?? 'No improvement priority was included in this approved review.'); ?></strong></div>
        </div>
      </article>

      <section class="ai-approved-review-section ai-research-review-section" aria-labelledby="ai-research-review-title">
        <div class="ai-studio-section-heading ai-mentor-perspective-heading">
          <p class="eyebrow">AI Research Review</p>
          <h2 id="ai-research-review-title">Your approved preparation review</h2>
          <p>This review reflects submitted research and presentation preparation. It is separate from the official presentation rubric.</p>
          <?php if ($aiReviewIncludedDocument): ?><p class="form-note">Your uploaded document was included in this AI Mentor review.</p><?php endif; ?>
        </div>

        <div class="ai-studio-overview ai-approved-review-overview">
          <article class="ai-score-card ai-mentor-guidance-card">
            <p class="eyebrow">Approved Research Result</p>
            <?php if ($aiMentorHasValidTotal): ?>
              <div class="ai-score-ring" style="--ai-score: <?php echo e((string) $aiMentorTotal); ?>" aria-label="<?php echo e((string) $aiMentorTotal); ?> out of 100"><strong><?php echo e((string) $aiMentorTotal); ?></strong><span>/100</span></div>
            <?php else: ?>
              <div class="ai-score-unavailable" role="status"><strong>Not included</strong><span>No valid overall research result was stored with this approved review.</span></div>
            <?php endif; ?>
            <div>
              <h3>Keep growing with purpose.</h3>
              <p><?php echo e($aiMentorSummary !== '' ? $aiMentorSummary : 'No summary was included in this approved review.'); ?></p>
              <?php if ($aiMentorHasSuggestedTokens): ?><span class="ai-token-award"><?php echo e((string) $aiMentorSuggestedTokens); ?> approved tokens</span><?php endif; ?>
            </div>
          </article>
          <article class="ai-topic-card ai-mentor-topic-card">
            <p class="eyebrow">Reviewed Context</p>
            <h3><?php echo e($aiMentorTopic !== '' ? $aiMentorTopic : 'Topic not recorded'); ?></h3>
            <p><?php echo e($aiMentorCategory !== '' ? $aiMentorCategory : 'Category not recorded'); ?></p>
            <small>Review status: Applied</small>
          </article>
        </div>

        <div class="ai-research-review-card">
          <?php foreach ($aiResearchCategories as $aiKey => [$aiLabel, $aiMaximum]): ?>
            <?php
              $aiCategoryValid = array_key_exists($aiKey, $approvedAiReview)
                  && is_numeric($approvedAiReview[$aiKey])
                  && (int) $approvedAiReview[$aiKey] >= 0
                  && (int) $approvedAiReview[$aiKey] <= $aiMaximum;
              $aiCategoryScore = $aiCategoryValid ? (int) $approvedAiReview[$aiKey] : null;
            ?>
            <div class="ai-research-metric">
              <div><span><?php echo e($aiLabel); ?></span><strong><?php echo $aiCategoryValid ? e((string) $aiCategoryScore) . ' / ' . e((string) $aiMaximum) : 'Not included'; ?></strong></div>
              <?php if ($aiCategoryValid): ?><div class="ai-metric-track" role="img" aria-label="<?php echo e($aiLabel . ': ' . $aiCategoryScore . ' out of ' . $aiMaximum); ?>"><i style="width: <?php echo e((string) round(($aiCategoryScore / $aiMaximum) * 100)); ?>%"></i></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="ai-feedback-grid">
          <article class="ai-feedback-card ai-strengths-card"><div class="ai-feedback-title"><span aria-hidden="true"></span><div><p class="eyebrow">Start With Strengths</p><h3>Carry these strengths forward</h3></div></div><?php if ($aiMentorStrengths !== []): ?><ul><?php foreach ($aiMentorStrengths as $strength): ?><li><?php echo e((string) $strength); ?></li><?php endforeach; ?></ul><?php else: ?><p>No strengths were included in this approved review.</p><?php endif; ?></article>
          <article class="ai-feedback-card ai-priority-action-card"><p class="eyebrow">Recommended Next Step</p><h3>One thoughtful step</h3><p><?php echo e($aiMentorRecommendedNextStep !== '' ? $aiMentorRecommendedNextStep : 'No recommended next step was included in this approved review.'); ?></p></article>
          <article class="ai-feedback-card ai-improvements-card"><div class="ai-feedback-title"><span aria-hidden="true"></span><div><p class="eyebrow">Improvement Opportunities</p><h3>Build on what is already working</h3></div></div><?php if ($aiMentorImprovements !== []): ?><ul><?php foreach ($aiMentorImprovements as $improvement): ?><li><?php echo e((string) $improvement); ?></li><?php endforeach; ?></ul><?php else: ?><p>No improvement opportunities were included in this approved review.</p><?php endif; ?></article>
          <article class="ai-feedback-card ai-coaching-note"><p class="eyebrow">Communication Note</p><h3>Clarity and communication</h3><p><?php echo e($aiMentorCommunicationNote !== '' ? $aiMentorCommunicationNote : 'No communication note was included in this approved review.'); ?></p></article>
          <article class="ai-feedback-card ai-milestone-note"><p class="eyebrow">Leadership Note</p><h3>The leader you are becoming</h3><p><?php echo e($aiMentorLeadershipNote !== '' ? $aiMentorLeadershipNote : 'No leadership note was included in this approved review.'); ?></p></article>
        </div>
      </section>

      <section class="ai-approved-review-section ai-official-rubric-section" aria-labelledby="ai-official-rubric-title">
        <div class="ai-studio-section-heading ai-rubric-heading">
          <p class="eyebrow">Official Presentation Rubric</p>
          <h2 id="ai-official-rubric-title">Presentation evaluation</h2>
          <p>This rubric comes from the existing YUVA Club presentation evaluation record. It is not part of the AI research review above.</p>
        </div>
        <?php if ($rubricCompleted > 0): ?>
          <div class="ai-presentation-rubric">
            <div class="ai-rubric-total"><span>Official rubric total</span><strong><?php echo e((string) $rubricScore); ?> <small>/ 100</small></strong><p><?php echo e((string) $rubricCompleted); ?> of <?php echo e((string) count(rubric_categories())); ?> categories scored</p></div>
            <div class="ai-rubric-list"><?php foreach (rubric_categories() as $rubricKey => $rubricLabel): ?><p><span><?php echo e($rubricLabel); ?></span><strong><?php echo ($record['rubric_' . $rubricKey] ?? '') !== '' ? e((string) $record['rubric_' . $rubricKey]) . ' / 10' : 'Not scored'; ?></strong></p><?php endforeach; ?></div>
          </div>
        <?php else: ?>
          <div class="ai-rubric-empty" role="status"><strong>No official rubric scores recorded</strong><p>The approved AI research review remains available above. An official presentation rubric will appear only after rubric scores are recorded.</p></div>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <div class="ai-studio-section-heading ai-roadmap-heading"><p class="eyebrow">Looking Ahead</p><h2>Future mentor experiences</h2><p>These experiences are planned for future YUVA Club updates.</p></div>
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
      $profileSchool = trim((string) ($student['School'] ?? ''));
      $profileGrade = trim((string) ($student['Grade'] ?? ''));
      $profilePresentations = max(0, (int) ($record['presentations'] ?? 0));
      $profileVolunteerHours = max(0, (float) ($record['service_hours'] ?? 0));
      $profileCertificateCount = $certificateReady ? 1 : 0;
      $profileBadgeCount = count($badges);
      $profileIsIncomplete = $profileFullName === '' || $profileGrade === '' || $profileSchool === '';
      $profileVolunteerLabel = $profileVolunteerHours > 0
          ? rtrim(rtrim(number_format($profileVolunteerHours, 1, '.', ''), '0'), '.') . ' hours'
          : 'No volunteer hours recorded';
    ?>
    <header class="profile-identity-header ds-story-hero profile-story-hero">
      <div class="profile-avatar-state">
        <div class="profile-initials yuva-public-avatar" aria-label="YUVA avatar: <?php echo e($publicAvatar['label']); ?>"><?php echo e($publicAvatar['icon']); ?></div>
        <span><?php echo e($publicAvatar['label']); ?></span>
      </div>
      <div class="profile-identity-copy">
        <p class="eyebrow">My Profile</p>
        <h1><?php echo e($name); ?></h1>
        <p>Your private profile and privacy-safe YUVA community identity.</p>
        <p>YUVA ID: <strong><?php echo e($studentId); ?></strong></p>
        <div class="profile-identity-badges"><span><?php echo e($level); ?></span><span><?php echo e($membershipGroupLabel); ?></span></div>
      </div>
      <a class="button primary profile-primary-action" href="#app-progress">View Leadership Journey</a>
    </header>

    <?php if ($profileIsIncomplete): ?>
      <div class="profile-state-banner" role="status" aria-live="polite">
        <strong>Your profile is still taking shape.</strong>
        <span>Some registered details are not available yet. The YUVA Club team can help correct identity information safely.</span>
      </div>
    <?php endif; ?>

    <section class="profile-public-identity" aria-labelledby="public-identity-title">
      <div class="profile-section-heading ds-section-heading"><p class="eyebrow">Your YUVA Identity</p><h2 id="public-identity-title">Choose how the YUVA community recognizes you</h2><p>This identity may appear in future challenges and leaderboards. Your real name and contact details are not included.</p></div>
      <?php if ($status === 'identity-saved'): ?><div class="profile-state-banner" role="status"><strong>Your YUVA Identity was saved.</strong></div><?php endif; ?>
      <?php if ($publicIdentityError !== ''): ?><div class="profile-state-banner identity-error" role="alert"><strong><?php echo e($publicIdentityError); ?></strong></div><?php endif; ?>
      <div class="identity-editor-grid">
        <div class="identity-preview" aria-label="Public identity preview"><span class="identity-preview-avatar"><?php echo e($publicAvatar['icon']); ?></span><strong><?php echo e($publicIdentity['handle'] ?: $studentId); ?></strong><?php if ($publicIdentity['handle']): ?><small><?php echo e($studentId); ?></small><?php endif; ?><p>Public identity preview</p></div>
        <form class="identity-form" action="student-public-identity.php" method="post">
          <?php echo csrf_field(); ?><input type="hidden" name="yuva_id" value="<?php echo e($studentId); ?>">
          <div class="field"><label for="public-handle">YUVA Handle <small>(optional)</small></label><input id="public-handle" name="public_handle" type="text" minlength="3" maxlength="24" pattern="[A-Za-z0-9][A-Za-z0-9._-]*[A-Za-z0-9]" value="<?php echo e((string)($publicIdentity['handle']??'')); ?>" aria-describedby="handle-help"><small id="handle-help">3–24 letters or numbers; single dots, underscores, or hyphens are allowed. Handles can change once every 30 days.</small></div>
          <fieldset class="avatar-picker"><legend>Choose a preset avatar</legend><div class="avatar-options"><?php foreach(\YuvaClub\Identity\PublicStudentIdentity::AVATARS as $avatarCode=>$avatar): ?><label><input type="radio" name="avatar_code" value="<?php echo e($avatarCode); ?>" <?php echo $publicIdentity['avatar_code']===$avatarCode?'checked':''; ?> required><span aria-hidden="true"><?php echo e($avatar['icon']); ?></span><small><?php echo e($avatar['label']); ?></small></label><?php endforeach; ?></div></fieldset>
          <p class="identity-permanent-note"><strong>Your YUVA ID is permanent:</strong> <?php echo e($studentId); ?></p>
          <button class="button primary" type="submit">Save YUVA Identity</button>
        </form>
      </div>
    </section>

    <section class="profile-overview" aria-labelledby="profile-overview-title">
      <div class="profile-section-heading ds-section-heading"><p class="eyebrow">Growth Snapshot</p><h2 id="profile-overview-title">Your journey at a glance</h2><p>These summaries use only your current approved YUVA records.</p></div>
      <div class="profile-summary-grid">
        <article class="profile-summary-card profile-summary-presentations"><span>Presentations</span><strong><?php echo e((string) $profilePresentations); ?></strong><p><?php echo $profilePresentations > 0 ? 'Recorded in your current progress.' : 'No presentations recorded yet.'; ?></p></article>
        <article class="profile-summary-card profile-summary-leadership"><span>Leadership level</span><strong><?php echo e($level); ?></strong><p><?php echo e($challengeStage); ?></p></article>
        <article class="profile-summary-card profile-summary-service"><span>Volunteer hours</span><strong><?php echo e($profileVolunteerLabel); ?></strong><p>Only approved hours appear here.</p></article>
        <article class="profile-summary-card profile-summary-certificates"><span>Certificates</span><strong><?php echo e((string) $profileCertificateCount); ?></strong><p><?php echo $certificateReady ? e($certificateStatus) : 'No certificate earned yet.'; ?></p></article>
        <article class="profile-summary-card profile-summary-badges"><span>Earned badges</span><strong><?php echo e((string) $profileBadgeCount); ?></strong><p><?php echo $profileBadgeCount > 0 ? 'Verified badges in your achievements.' : 'No badge earned yet.'; ?></p></article>
      </div>
    </section>

    <div class="profile-content-grid">
      <article class="profile-card profile-about-card">
        <div class="profile-card-heading"><span class="profile-card-icon profile-about-icon" aria-hidden="true"></span><div><p class="eyebrow">Identity</p><h2>About you</h2><p>Registered identity details are read-only.</p></div></div>
        <dl class="profile-detail-list">
          <div><dt>Registered name</dt><dd><?php echo e($profileFullName !== '' ? $profileFullName : 'Registered name is unavailable.'); ?></dd></div>
          <div><dt>Preferred name</dt><dd><?php echo e($profilePreferredName !== '' ? $profilePreferredName : 'No preferred name recorded.'); ?></dd></div>
          <div><dt>YUVA ID</dt><dd><?php echo e($studentId); ?></dd></div>
          <div><dt>Program</dt><dd><?php echo e($membershipGroupLabel); ?></dd></div>
          <div><dt>School</dt><dd><?php echo e($profileSchool !== '' ? $profileSchool : 'No school recorded.'); ?></dd></div>
          <div><dt>Grade</dt><dd><?php echo e($profileGrade !== '' ? $profileGrade : 'No grade recorded.'); ?></dd></div>
          <div><dt>Student email</dt><dd><?php echo e($profileValue($student, 'Student Email', 'No student email recorded.')); ?></dd></div>
        </dl>
      </article>

      <article class="profile-card profile-goals-card">
        <div class="profile-card-heading"><span class="profile-card-icon profile-goals-icon" aria-hidden="true"></span><div><p class="eyebrow">Purpose</p><h2>Your goals</h2><p>Your own growth direction belongs here.</p></div></div>
        <div class="profile-honest-empty"><strong>No goals recorded.</strong><p>Release 1.0 does not yet have a safe student profile-editing workflow, so goals remain read-only and unavailable.</p></div>
        <dl class="profile-detail-list">
          <div><dt>Interests</dt><dd><?php echo e($profileValue($student, 'Interests', 'No interests recorded.')); ?></dd></div>
          <div><dt>Why you joined</dt><dd><?php echo e($profileValue($student, 'Why Join', 'No motivation recorded.')); ?></dd></div>
        </dl>
      </article>

      <article class="profile-card profile-recognition-card">
        <div class="profile-card-heading"><span class="profile-card-icon profile-leadership-icon" aria-hidden="true"></span><div><p class="eyebrow">Leadership</p><h2>Your current growth</h2><p>Approved progress only.</p></div></div>
        <dl class="profile-detail-list">
          <div><dt>Leadership level</dt><dd><?php echo e($level); ?></dd></div>
          <div><dt>Rank status</dt><dd><?php echo e($record['rank_status'] ?? 'Approved'); ?></dd></div>
          <div><dt>Points</dt><dd><?php echo e((string) $points); ?></dd></div>
          <div><dt>Current challenge stage</dt><dd><?php echo e($challengeStage); ?></dd></div>
          <div><dt>Leadership milestone</dt><dd><?php echo e(($record['leadership_milestones'] ?? '') !== '' ? $record['leadership_milestones'] : 'No leadership milestone recorded yet.'); ?></dd></div>
        </dl>
        <a class="profile-text-link" href="#app-achievements">Review achievements</a>
      </article>

      <article class="profile-card profile-connections-card">
        <div class="profile-card-heading"><span class="profile-card-icon profile-contact-icon" aria-hidden="true"></span><div><p class="eyebrow">Connections</p><h2>Support around you</h2><p>Private connection status without contact details.</p></div></div>
        <div class="profile-connection-state <?php echo $profileParentConnected ? 'is-connected' : 'is-unavailable'; ?>">
          <span aria-hidden="true"></span>
          <div><strong><?php echo $profileParentConnected ? 'Parent or guardian connected' : 'Parent connection unavailable'; ?></strong><p><?php echo $profileParentConnected ? 'A parent or guardian connection is recorded for your account.' : 'No supported parent or guardian connection is available in your current record.'; ?></p></div>
        </div>
        <div class="profile-connection-state is-unavailable">
          <span aria-hidden="true"></span>
          <div><strong>Google Login unavailable</strong><p>Google Login is not implemented for YUVA Club accounts in Release 1.0.</p></div>
        </div>
      </article>

      <article class="profile-card profile-account-card">
        <div class="profile-card-heading"><span class="profile-card-icon profile-account-icon" aria-hidden="true"></span><div><p class="eyebrow">Account &amp; Security</p><h2>Protect your account</h2><p>Use the existing secure account routes.</p></div></div>
        <ul class="profile-security-list">
          <li><strong>Password help</strong><span>Request a reset through the verified account-recovery flow.</span><a href="forgot-password.php?account=student">Open password help</a></li>
          <li><strong>Private profile</strong><span>Your profile is available only after student authentication.</span></li>
          <li><strong>Managed details</strong><span>Identity, program, progress, certificates, and badges cannot be edited here.</span></li>
          <li><strong>Student settings</strong><span>Review supported account, privacy, accessibility, and help options.</span><a href="#app-settings">Open settings</a></li>
        </ul>
        <a class="button ghost profile-logout-button" href="portal-logout.php">Log Out</a>
      </article>
    </div>
  </section>

  <section class="band app-section" id="app-settings" data-app-section="profile" aria-labelledby="settings-title">
    <?php
      $settingsEmail = normalize_email((string) ($student['Student Email'] ?? ''));
      $settingsHasUsableEmail = $settingsEmail !== '' && !str_ends_with($settingsEmail, '.invalid');
    ?>
    <header class="settings-hero ds-story-hero">
      <div>
        <p class="eyebrow">Student Settings</p>
        <h1 id="settings-title">Your account, clearly explained.</h1>
        <p>Review the account and support options that genuinely exist in YUVA Club Release 1.0.</p>
      </div>
      <span class="settings-hero-mark" aria-hidden="true"><?php echo student_app_icon('settings'); ?></span>
    </header>

    <div class="settings-primary-panel">
      <div>
        <p class="eyebrow">Account security</p>
        <h2>Password help is available</h2>
        <p>Use the existing verified recovery flow if you need to create or reset your student password.</p>
      </div>
      <a class="button primary settings-primary-action" href="forgot-password.php?account=student">Open password help</a>
    </div>

    <div class="settings-content-grid">
      <article class="settings-card settings-account-card">
        <div class="settings-card-heading"><span class="settings-card-icon settings-account-icon" aria-hidden="true"></span><div><p class="eyebrow">Account Summary</p><h2>Your student account</h2></div></div>
        <dl class="settings-detail-list">
          <div><dt>Student</dt><dd><?php echo e($name); ?></dd></div>
          <div><dt>YUVA ID</dt><dd><?php echo e($studentId); ?></dd></div>
          <div><dt>Account role</dt><dd>Student</dd></div>
          <div><dt>Program</dt><dd><?php echo e($membershipGroupLabel); ?></dd></div>
          <div><dt>Authentication</dt><dd>Authenticated student account</dd></div>
          <div><dt>Recovery email</dt><dd><?php echo e($settingsHasUsableEmail ? $settingsEmail : 'No directly usable student recovery email is available.'); ?></dd></div>
        </dl>
      </article>

      <article class="settings-card settings-session-card">
        <div class="settings-card-heading"><span class="settings-card-icon settings-session-icon" aria-hidden="true"></span><div><p class="eyebrow">Session &amp; Security</p><h2>Protect this session</h2></div></div>
        <ul class="settings-status-list">
          <li><strong>Authenticated access</strong><span>This page is available only through your current student session.</span></li>
          <li><strong>Shared device?</strong><span>Log out when you finish, especially on a school, library, or family device.</span></li>
          <li><strong>Account details</strong><span>Never share your password, reset link, date of birth, or access code.</span></li>
        </ul>
        <a class="button ghost settings-logout-action" href="portal-logout.php">Log out of YUVA Club</a>
      </article>

      <article class="settings-card settings-notification-card">
        <div class="settings-card-heading"><span class="settings-card-icon settings-notification-icon" aria-hidden="true"></span><div><p class="eyebrow">Notifications</p><h2>Current notification status</h2></div></div>
        <div class="settings-availability is-available"><strong>In-app updates available</strong><p>Your current session, submission, approved-review, certificate, and club updates can appear in Student Notifications when real data exists.</p><a href="#app-notifications">Open notifications</a></div>
        <div class="settings-availability is-unavailable"><strong>Notification preferences unavailable</strong><p>Release 1.0 does not provide email, push, frequency, or opt-out controls in Student Settings.</p></div>
      </article>

      <article class="settings-card settings-accessibility-card">
        <div class="settings-card-heading"><span class="settings-card-icon settings-accessibility-icon" aria-hidden="true"></span><div><p class="eyebrow">Accessibility</p><h2>Designed for different ways of using the app</h2></div></div>
        <ul class="settings-status-list">
          <li><strong>Keyboard and focus</strong><span>Navigation and actions include visible focus support.</span></li>
          <li><strong>Motion</strong><span>The app respects your device’s reduced-motion preference.</span></li>
          <li><strong>Responsive layout</strong><span>Student experiences adapt across mobile, tablet, and desktop screens.</span></li>
        </ul>
        <a class="settings-text-link" href="safety.html">Read safety and accessibility information</a>
      </article>

      <article class="settings-card settings-legal-card">
        <div class="settings-card-heading"><span class="settings-card-icon settings-legal-icon" aria-hidden="true"></span><div><p class="eyebrow">Privacy &amp; Policies</p><h2>Understand how YUVA Club works</h2></div></div>
        <nav class="settings-link-list" aria-label="Student privacy and policy links">
          <a href="privacy.html"><strong>Privacy Policy</strong><span>How account and student information is handled.</span></a>
          <a href="terms.html"><strong>Terms of Service</strong><span>The rules for using YUVA Club.</span></a>
          <a href="safety.html"><strong>Child Safety</strong><span>Safety principles and reporting guidance.</span></a>
        </nav>
      </article>

      <article class="settings-card settings-support-card">
        <div class="settings-card-heading"><span class="settings-card-icon settings-support-icon" aria-hidden="true"></span><div><p class="eyebrow">Help &amp; Support</p><h2>Use the right support path</h2></div></div>
        <p>For general guidance, use the verified public contact page. For a safety or platform concern, use the authenticated report form.</p>
        <div class="settings-support-actions"><a class="settings-text-link" href="contact.html">Open support information</a><a class="settings-text-link" href="#safety-report">Report an issue</a></div>
      </article>
    </div>

    <section class="settings-unavailable" aria-labelledby="settings-unavailable-title">
      <div class="settings-section-heading ds-section-heading"><p class="eyebrow">Not Available in Release 1.0</p><h2 id="settings-unavailable-title">Settings we do not pretend to support</h2><p>These controls require separately reviewed product and security work.</p></div>
      <div class="settings-unavailable-grid">
        <article><strong>Connected accounts</strong><p>Google and other identity-provider linking are not implemented.</p></article>
        <article><strong>Theme and language</strong><p>Theme switching and language selection are not configurable.</p></article>
        <article><strong>Email and push preferences</strong><p>Per-channel notification controls are not available.</p></article>
        <article><strong>Data export or deletion</strong><p>There is no self-service workflow. Review the Privacy Policy and verified Contact page for guidance.</p></article>
      </div>
    </section>
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
    <div class="practice-hero studio-hero studio-hero-practice ds-workspace-hero">
      <div>
        <p class="eyebrow">Practice Studio</p>
        <h1>Build your speaking skills</h1>
        <p>Choose a topic, organize your research, and prepare with confidence.</p>
      </div>
      <div class="practice-hero-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
      </div>
    </div>

    <div class="practice-continue-card studio-card studio-card-featured">
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

    <article class="practice-submission-status practice-submission-status-<?php echo e($submissionPresentation['tone']); ?>" aria-labelledby="practice-submission-status-title">
      <span class="practice-submission-status-mark" aria-hidden="true"></span>
      <div <?php echo $submissionIsError ? 'role="alert"' : 'role="status" aria-live="polite"'; ?>>
        <p class="eyebrow"><?php echo e($submissionPresentation['eyebrow']); ?></p>
        <h2 id="practice-submission-status-title"><?php echo e($submissionPresentation['title']); ?></h2>
        <p><?php echo e($submissionPresentation['body']); ?></p>
      </div>
      <?php if ($submissionState === \YuvaClub\Submission\ResearchSubmissionState::REVIEW_APPROVED): ?><a class="button ghost" href="#app-ai-coach">Open AI Mentor</a>
      <?php elseif ($submissionState !== \YuvaClub\Submission\ResearchSubmissionState::SUBMISSION_RECEIVED && $submissionState !== \YuvaClub\Submission\ResearchSubmissionState::REVIEW_NOT_STARTED && $submissionState !== \YuvaClub\Submission\ResearchSubmissionState::REVIEW_PROCESSING && $submissionState !== \YuvaClub\Submission\ResearchSubmissionState::REVIEW_PENDING_APPROVAL): ?><a class="button ghost" href="#research-submission">Review Submission</a><?php endif; ?>
    </article>

    <div class="practice-section-heading">
      <p class="eyebrow">Practice Tools</p>
      <h2>Your preparation workspace</h2>
    </div>

    <span class="app-anchor" id="topic-selection" aria-hidden="true"></span>
    <div class="practice-primary-grid">
      <form class="form-card practice-workspace-card practice-topic-card studio-card" action="portal-submit-topic.php" method="post">
        <?php echo csrf_field(); ?>
        <div class="practice-card-heading studio-card-heading">
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

      <div class="form-card practice-workspace-card practice-selection-summary studio-card">
        <div class="practice-card-heading studio-card-heading">
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
    <form class="form-card practice-workspace-card practice-research-card studio-card" action="portal-submit-research.php" method="post" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo e((string) \YuvaClub\Submission\ResearchUploadValidator::MAX_BYTES); ?>">
      <div class="practice-card-heading studio-card-heading">
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
          <input id="research_file" name="research_file" type="file" accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.jpeg,.png" aria-describedby="research-file-help<?php echo $submissionIsError ? ' research-file-error' : ''; ?>"<?php echo $submissionIsError ? ' aria-invalid="true"' : ''; ?>>
          <p id="research-file-help" class="form-note">Optional. PDF, PPT, PPTX, DOC, DOCX, JPG, JPEG, or PNG. Maximum 10 MB.</p>
          <?php if ($submissionIsError): ?><p id="research-file-error" class="field-error"><?php echo e($submissionPresentation['body']); ?></p><?php endif; ?>
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

  <section class="band" id="app-challenges">
    <div class="section-head"><p class="eyebrow">Challenges</p><h2>Today’s &amp; Weekly Challenges</h2><p>Practice, improve your personal best, or join an asynchronous Quick Challenge. You never need to be online at the same time as another student.</p></div>
    <?php if($competitionStudentNotice!==''): ?><div class="form-status success"><?php echo e($competitionStudentNotice); ?></div><?php endif; ?>
    <?php if($competitionStudentError!==''): ?><div class="form-status error" role="alert"><?php echo e($competitionStudentError); ?></div><?php endif; ?>
    <?php if($quickChallengeAttempt!==null): ?><article class="form-card" id="quick-challenge-attempt"><p class="eyebrow">Attempt <?php echo e((string)$quickChallengeAttempt['attempt_number']); ?> · Server timed</p><h3><?php echo e((string)$quickChallengeAttempt['prompt']); ?></h3><p>Preparation ends: <?php echo e((string)$quickChallengeAttempt['started_at']); ?><br>Response deadline: <?php echo e((string)$quickChallengeAttempt['response_deadline_at']); ?></p><form action="student-quick-challenge-submit.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="attempt_guid" value="<?php echo e((string)$quickChallengeAttempt['attempt_guid']); ?>"><input type="hidden" name="row_version" value="<?php echo e((string)$quickChallengeAttempt['row_version']); ?>"><label>Your response<textarea name="response_text" maxlength="12000" required></textarea></label><button class="button primary" type="submit">Submit Attempt</button></form></article><?php endif; ?>
    <?php if($quickChallengeSubmittedAttempt!==''): ?><article class="form-card"><p class="eyebrow">AI Mentor Practice Coaching</p><h3>Analyze your locked attempt</h3><p>Your immutable submitted response will be evaluated once. Refreshing will not create another AI call.</p><form action="student-quick-challenge-analyze.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="attempt_guid" value="<?php echo e($quickChallengeSubmittedAttempt); ?>"><button class="button primary" type="submit">Analyze My Challenge</button></form></article><?php endif; ?>
    <?php foreach($quickChallengeResults as$result): if(($result['status']??'')!=='Completed')continue; $gap=(int)$result['total_score']-(int)$result['benchmark_score']; ?><article class="form-card quick-challenge-result"><p class="eyebrow">Your Result · Practice Score</p><h3><?php echo e((string)$result['display_name']); ?></h3><p><strong><?php echo e((string)$result['total_score']); ?> / 100</strong> · Personal Best <?php echo e((string)($result['best_score']??$result['total_score'])); ?></p><p><strong><?php echo e((string)$result['benchmark_label']); ?>: <?php echo e((string)$result['benchmark_score']); ?></strong><br><?php echo $gap>=0?'🏆 Benchmark Beaten':e((string)abs($gap)).' points to go'; ?></p><?php foreach($result['components'] as$component): ?><p><?php echo e((string)$component['dimension']); ?> <strong><?php echo e((string)$component['score']); ?></strong></p><?php endforeach; ?><h4>What You Did Well</h4><ul><?php foreach(($result['coaching']['strengths']??[]) as$item): ?><li><?php echo e((string)$item); ?></li><?php endforeach; ?></ul><h4>Try Next</h4><ul><?php foreach(($result['coaching']['improvements']??[]) as$item): ?><li><?php echo e((string)$item); ?></li><?php endforeach; ?></ul><p><strong>Practice Mission:</strong> <?php echo e((string)($result['coaching']['practice_mission']??'')); ?></p></article><?php endforeach; ?>
    <div class="three-grid">
    <?php foreach($quickChallenges as$challenge): ?><article class="form-card"><p class="eyebrow"><?php echo e((string)$challenge['experience_mode']); ?> · <?php echo e((string)$challenge['difficulty']); ?></p><h3><?php echo e((string)$challenge['title']); ?></h3><p><?php echo e((string)$challenge['description']); ?></p><p><strong>Prompt:</strong> <?php echo e((string)$challenge['prompt_text']); ?></p><p><?php echo e((string)$challenge['division_name']); ?> · Preparation <?php echo e((string)$challenge['preparation_seconds']); ?>s · Response <?php echo e((string)$challenge['response_seconds']); ?>s<br>Attempts: <?php echo e((string)($challenge['attempt_count']??0)); ?>/<?php echo e((string)$challenge['maximum_attempts']); ?> · Deadline <?php echo e((string)$challenge['submission_deadline']); ?> UTC</p><?php if($challenge['best_score']!==null): ?><p><strong>Personal best: <?php echo e((string)$challenge['best_score']); ?></strong><br><small>Score version <?php echo e((string)$challenge['score_version']); ?></small></p><?php endif; ?><?php if($challenge['status']==='Open'&&(int)($challenge['attempt_count']??0)<(int)$challenge['maximum_attempts']): ?><form action="student-quick-challenge-start.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="competition_guid" value="<?php echo e((string)$challenge['competition_guid']); ?>"><input type="hidden" name="competition_division_id" value="<?php echo e((string)$challenge['competition_division_id']); ?>"><button class="button primary" type="submit">Start Challenge</button></form><?php endif; ?></article><?php endforeach; ?>
    <?php if($quickChallenges===[]): ?><div class="form-card"><p>No Quick Challenges are available right now.</p></div><?php endif; ?>
    </div>
    <div class="section-head"><h2>Formal &amp; Organization Available Challenges</h2><p>Official submissions remain immutable under the Phase 2C.1 rules.</p></div>
    <div class="three-grid">
    <?php foreach($availableChallenges as $challenge): ?><article class="form-card"><p class="eyebrow"><?php echo e((string)$challenge['scope_type']); ?> · <?php echo e((string)$challenge['status']); ?></p><h3><?php echo e((string)$challenge['title']); ?></h3><p><?php echo e((string)$challenge['description']); ?></p><p><strong><?php echo e((string)$challenge['division_name']); ?></strong><br><?php echo e((string)$challenge['rubric_name']); ?> · max <?php echo e((string)$challenge['maximum_score']); ?><br>Deadline: <?php echo e((string)$challenge['submission_deadline']); ?> UTC</p><?php if(empty($challenge['entry_guid'])&&$challenge['status']==='Open'): ?><form action="student-competition-entry.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="competition_guid" value="<?php echo e((string)$challenge['competition_guid']); ?>"><input type="hidden" name="competition_division_id" value="<?php echo e((string)$challenge['competition_division_id']); ?>"><button class="button primary" type="submit">Join Challenge</button></form><?php elseif(!empty($challenge['entry_guid'])): ?><p><strong>Joined</strong></p><?php endif; ?></article><?php endforeach; ?>
    <?php if($availableChallenges===[]): ?><div class="form-card"><p>No eligible challenges are available right now.</p></div><?php endif; ?>
    </div>
    <div class="section-head"><h2>My Entries</h2></div><div class="three-grid">
    <?php foreach($challengeEntries as $entry): ?><article class="form-card"><h3><?php echo e((string)$entry['title']); ?></h3><p><?php echo e((string)$entry['division_name']); ?> · <strong><?php echo e((string)$entry['entry_status']); ?></strong></p><?php if(!empty($entry['submission_guid'])): ?><p><strong>Submission Locked</strong></p><p>You can continue practicing in YUVA Club, but your official competition submission will not change.</p><?php elseif($entry['status']==='Open'): ?><form action="student-competition-submit.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="entry_guid" value="<?php echo e((string)$entry['entry_guid']); ?>"><button class="button primary" type="submit">Submit Competition Entry</button></form><?php endif; ?></article><?php endforeach; ?>
    <?php if($challengeEntries===[]): ?><div class="form-card"><p>You have not joined a challenge yet.</p></div><?php endif; ?>
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

  <section class="band" id="organization-membership">
    <div class="section-head"><p class="eyebrow">Organization membership</p><h2>Your school or organization connections</h2><p>You decide whether to accept a request. If you are under 18—or your date of birth is unavailable—a linked parent or guardian must also approve before access becomes active.</p></div>
    <?php if ($organizationMemberships === []): ?><div class="form-card"><p>No organization invitations or memberships are waiting for you.</p></div><?php endif; ?>
    <div class="three-grid">
    <?php foreach ($organizationMemberships as $membership): ?><article class="form-card">
      <p class="eyebrow"><?php echo e((string) $membership['status']); ?></p><h3><?php echo e((string) $membership['organization_code']); ?></h3>
      <p><?php echo e((string) $membership['invitation_purpose']); ?></p>
      <?php if (!empty($membership['invitation_message'])): ?><p><?php echo e((string) $membership['invitation_message']); ?></p><?php endif; ?>
      <?php if (($membership['status'] ?? '') === 'Invited'): ?><form action="student-organization-membership-action.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="membership_guid" value="<?php echo e((string) $membership['membership_guid']); ?>"><button class="button primary" name="decision" value="accept" type="submit">Accept</button> <button class="button ghost" name="decision" value="decline" type="submit">Decline</button></form><?php elseif (($membership['status'] ?? '') === 'ParentApprovalPending'): ?><p><strong>Waiting for parent/guardian approval.</strong></p><?php endif; ?>
    </article><?php endforeach; ?>
    </div>
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

  <section class="band app-section" id="app-subscription" data-app-section="profile" aria-labelledby="subscription-status-title">
    <div class="form-card" id="subscription-status"><p class="eyebrow">Membership plan</p><h2 id="subscription-status-title"><?php echo e((string)$subscriptionStatus['display_name']); ?></h2><?php if($subscriptionStatus['source_type']==='PREMIUM_BETA_PROMO'): ?><p>YUVA Beta Program<?php if($subscriptionStatus['ends_at']!==null): ?><br>Valid until: <?php echo e((string)$subscriptionStatus['ends_at']); ?> UTC<?php endif; ?></p><?php endif; ?><p>Your plan status is private account information. Current YUVA learning and AI Mentor behavior is unchanged in this foundation release.</p><?php if($subscriptionStudentNotice!==''): ?><div class="form-status success"><?php echo e($subscriptionStudentNotice); ?></div><?php endif; ?><?php if($subscriptionStudentError!==''): ?><div class="form-status error"><?php echo e($subscriptionStudentError); ?></div><?php endif; ?><form action="student-promo-redeem.php" method="post"><?php echo csrf_field(); ?><label>Premium beta invitation code<input name="invitation_code" autocomplete="off" maxlength="64" required></label><button class="button primary">Redeem invitation</button></form></div>
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
