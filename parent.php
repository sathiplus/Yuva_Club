<?php
require __DIR__ . '/portal-lib.php';

$parentContext = require_authenticated_parent();
$student = $parentContext['student'];
$parentChildren = portal_parent_login_workflow()->authorizedChildren($_SESSION) ?? [];
$studentId = normalize_yuva_id(
    (string) ($_SESSION['parent_student_id'] ?? '')
);

$selection = read_json_file(topic_selections_file())[$studentId] ?? [];
$research = read_json_file(research_file())[$studentId] ?? [];
$record = student_record($studentId);
$approvalStatus = student_approval_status($studentId, $student);
$hub = hub_settings();
$badges = earned_badges($record);
$points = student_points($record);
$tokens = student_tokens($record);
$publicIdentity=public_student_identity($studentId);
$publicAvatar=\YuvaClub\Identity\PublicStudentIdentity::avatar($publicIdentity['avatar_code']);
$leadershipProgress=null;$leadershipHistory=[];
try{$leadershipProgress=leadership_eligibility_service()->latestByYuvaId($studentId);$leadershipHistory=leadership_eligibility_service()->history($studentId);}catch(Throwable $error){error_log('YUVA leadership parent view unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
$rewardLevel = reward_level($record);
$rank = approved_rank($record);
$eligibleRank = rank_eligibility($record);
$challengeStage = challenge_stage($record);
$rubricScore = rubric_score($record);
$mediaConsent = null;
if(ai_mentor_feature_enabled('media_analysis_enabled')&&database_settings_present()&&db_is_sqlsrv()){
    try{$mediaConsent=media_consent_service()->status($studentId);}catch(Throwable){$mediaConsent=null;}
}
$organizationMemberships = [];
$subscriptionStatus=['display_name'=>'Free','source_type'=>'default','ends_at'=>null];try{$subscriptionStatus=subscription_entitlement_service()->resolve($studentId);}catch(Throwable $error){error_log('YUVA parent subscription view unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
try {
    $organizationMemberships = organization_membership_service()->requestsForStudent($studentId);
} catch (Throwable $error) {
    error_log('YUVA parent organization membership view unavailable correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
}

portal_header('Parent Dashboard');
?>
<link rel="stylesheet" href="assets/parent-experience.css?v=1">
<a class="parent-skip-link" href="#parent-main">Skip to parent overview</a>
<main class="parent-experience" id="parent-main" tabindex="-1">
  <nav class="parent-local-nav" aria-label="Parent portal sections">
    <a class="parent-local-brand" href="#parent-overview"><img src="assets/yuva-symbol.png" alt=""><span><strong>YUVA</strong> Parent</span></a>
    <div><a href="#parent-overview">Overview</a><a href="#parent-growth">Growth</a><a href="#parent-presentations">Presentations</a><a href="#parent-mentor">AI Mentor</a><a href="#organization-membership">Organizations</a><a href="#parent-account">Account</a></div>
    <a class="parent-nav-logout" href="portal-logout.php">Log Out</a>
  </nav>

  <section class="parent-section" id="my-children" aria-labelledby="my-children-title">
    <div class="parent-section-heading"><div><p class="eyebrow">Parent Dashboard</p><h2 id="my-children-title">My Children</h2><p>Switch between students linked to your authenticated Parent account.</p></div></div>
    <div class="parent-mentor-grid"><?php foreach($parentChildren as $child): $childId=normalize_yuva_id((string)($child['yuva_id']??'')); ?><article class="parent-card"><h3><?php echo e(trim((string)($child['student_first_name']??'').' '.(string)($child['student_last_name']??''))); ?></h3><p><?php echo e($childId); ?></p><?php if($childId===$studentId): ?><strong>Currently viewing</strong><?php else: ?><form action="parent-select-child.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="student_id" value="<?php echo e($childId); ?>"><button class="button ghost" type="submit">View this child</button></form><?php endif; ?></article><?php endforeach; ?></div>
  </section>

  <section class="parent-hero" id="parent-overview" aria-labelledby="parent-overview-title">
    <div class="parent-hero-copy"><p class="eyebrow">Parent Experience</p><h1 id="parent-overview-title">How is <?php echo e(student_display_name($student)); ?> growing?</h1><p>A clear view of your child's verified leadership activity, preparation, and recognition.</p><div class="parent-identity"><span>YUVA Identity<strong><?php echo e($publicAvatar['icon'].' '.($publicIdentity['handle']?:$studentId)); ?></strong></span><span>YUVA ID<strong><?php echo e($studentId); ?></strong></span><span>Membership<strong><?php echo e(membership_group_label($student)); ?></strong></span><span>Approval<strong><?php echo e($approvalStatus); ?></strong></span></div></div>
    <div class="parent-hero-mark" aria-hidden="true"><span></span><i></i><b></b></div>
  </section>

  <section class="parent-section" id="parent-growth" aria-labelledby="parent-growth-title">
    <div class="parent-section-heading"><div><p class="eyebrow">Leadership Growth</p><h2 id="parent-growth-title">A meaningful view of progress</h2><p>These measures come directly from approved YUVA Club activity.</p></div><span class="parent-rank-chip"><?php echo e($rank); ?></span></div>
    <div class="parent-growth-grid">
      <article class="parent-metric parent-metric-rank"><span class="parent-card-icon" aria-hidden="true"></span><p>Leadership Rank</p><strong><?php echo e($rank); ?></strong><small>Eligible: <?php echo e($eligibleRank); ?></small></article>
      <article class="parent-metric parent-metric-attendance"><span class="parent-card-icon" aria-hidden="true"></span><p>Attendance</p><strong><?php echo e($record['attendance'] ?? '0'); ?></strong><small>sessions</small></article>
      <article class="parent-metric parent-metric-presentations"><span class="parent-card-icon" aria-hidden="true"></span><p>Presentations</p><strong><?php echo e($record['presentations'] ?? '0'); ?></strong><small>verified</small></article>
      <article class="parent-metric parent-metric-hours"><span class="parent-card-icon" aria-hidden="true"></span><p>Volunteer Hours</p><strong><?php echo e($record['service_hours'] ?? '0'); ?></strong><small>approved</small></article>
      <article class="parent-metric parent-metric-rubric"><span class="parent-card-icon" aria-hidden="true"></span><p>Rubric Score</p><strong><?php echo e((string) $rubricScore); ?><small>/100</small></strong><small>official evaluation</small></article>
    </div>
    <?php if(is_array($leadershipProgress)): ?><article class="parent-progress-card"><div class="parent-card-heading"><div><p class="eyebrow">Leadership Journey</p><h2><?php echo e((string)$leadershipProgress['current_level']); ?> → <?php echo e((string)($leadershipProgress['target_level']??'Continued mentorship')); ?></h2><p><?php echo e((string)$leadershipProgress['status']); ?> · <?php echo e((string)$leadershipProgress['completed']); ?> of <?php echo e((string)$leadershipProgress['required']); ?> requirements complete</p></div></div><ul><?php foreach($leadershipProgress['requirements'] as $requirement): ?><li><?php echo !empty($requirement['complete'])?'✓':'○'; ?> <?php echo e((string)$requirement['label']); ?></li><?php endforeach; ?></ul><?php if($leadershipHistory!==[]): ?><h3>Approved level history</h3><ul><?php foreach($leadershipHistory as $history): ?><li><?php echo e((string)$history['previous_level']); ?> → <?php echo e((string)$history['new_level']); ?></li><?php endforeach; ?></ul><?php endif; ?></article><?php endif; ?>

    <article class="parent-progress-card">
      <div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">Leadership Challenge</p><h2><?php echo e($challengeStage); ?></h2><p>Current approved challenge stage</p></div></div>
      <div class="challenge-path parent-challenge-path"><?php foreach (challenge_stages() as $stage): ?><span class="<?php echo $stage === $challengeStage ? 'active' : ''; ?>"><?php echo e($stage); ?></span><?php endforeach; ?></div>
      <dl class="parent-detail-grid"><div><dt>Challenge Month</dt><dd><?php echo e($record['challenge_month'] ?? date('Y-m')); ?></dd></div><div><dt>Region</dt><dd><?php echo e($record['challenge_region'] ?? 'Online'); ?></dd></div><div><dt>Finalist Status</dt><dd><?php echo e($record['finalist_status'] ?? 'Not Qualified'); ?></dd></div><div><dt>Award Status</dt><dd><?php echo e($record['award_status'] ?? 'None'); ?></dd></div></dl>
      <a class="button ghost" href="leaderboard.php">View Challenge Leaderboard</a>
    </article>
  </section>

  <section class="parent-section" id="parent-presentations" aria-labelledby="parent-presentations-title">
    <div class="parent-section-heading"><div><p class="eyebrow">Presentations</p><h2 id="parent-presentations-title">Preparation and presentation activity</h2><p>See the current topic, research status, and available session history.</p></div></div>
    <div class="parent-two-grid">
      <article class="parent-card parent-presentation-card"><div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">Upcoming Presentation</p><h2><?php echo e($selection['topic_title'] ?? 'No topic selected'); ?></h2></div></div><dl class="parent-detail-list"><div><dt>Date</dt><dd><?php echo e($selection['presentation_date'] ?? 'Not scheduled'); ?></dd></div><div><dt>Time</dt><dd><?php echo e($selection['presentation_time'] ?? 'Not scheduled'); ?></dd></div><div><dt>Topic Status</dt><dd><?php echo e($selection['status'] ?? 'Pending'); ?></dd></div><div><dt>Research Status</dt><dd><?php echo e($research['status'] ?? 'Not Submitted'); ?></dd></div></dl></article>
      <article class="parent-card parent-recordings-card"><div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">Presentation History</p><h2>Session recordings</h2></div></div><div class="parent-link-list"><?php foreach (parse_link_lines($hub['recordings']) as $link): ?><a href="<?php echo e($link['url']); ?>" target="_blank" rel="noopener"><?php echo e($link['title']); ?><span aria-hidden="true">↗</span></a><?php endforeach; ?><?php if (trim($hub['recordings']) === ''): ?><div class="parent-empty"><strong>No recordings posted yet</strong><p>Approved session recordings will appear here when available.</p></div><?php endif; ?></div></article>
    </div>
  </section>

  <section class="parent-section" id="parent-mentor" aria-labelledby="parent-mentor-title">
    <div class="parent-section-heading"><div><p class="eyebrow">Guidance and Reflection</p><h2 id="parent-mentor-title">Support for the next step</h2><p>Existing teacher, mentor, and AI review information in one calm view.</p></div></div>
    <div class="parent-mentor-grid">
      <article class="parent-card parent-mentor-feature"><div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">AI Mentor Review</p><h2>Approved growth guidance</h2></div></div><p class="parent-guidance-summary"><?php echo e($record['ai_feedback_summary'] ?? 'No AI feedback yet.'); ?></p><dl class="parent-detail-list"><div><dt>Communication Skills</dt><dd><?php echo e($record['communication_skills'] ?? 'Not recorded yet.'); ?></dd></div><div><dt>Leadership Milestones</dt><dd><?php echo e($record['leadership_milestones'] ?? 'Not recorded yet.'); ?></dd></div><div><dt>Score</dt><dd><?php echo e($record['score'] ?? 'Optional'); ?></dd></div></dl></article>
      <article class="parent-card"><div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">People Supporting Growth</p><h2>Feedback</h2></div></div><div class="parent-feedback-list"><div><span>Teacher</span><p><?php echo e($record['teacher_feedback'] ?? 'No feedback yet.'); ?></p></div><div><span>Mentor</span><p><?php echo e($record['mentor_feedback'] ?? 'No mentor feedback yet.'); ?></p></div><div><span>Rank Status</span><p><?php echo e($record['rank_status'] ?? 'Approved'); ?></p></div></div></article>
      <?php if(is_array($mediaConsent)&&!empty($mediaConsent['parent_required'])): ?><article class="parent-card"><p class="eyebrow">Media AI permission</p><h2>Presentation coaching recordings</h2><p>Your child may upload audio or video that YUVA Club sends to OpenAI for presentation coaching. Analysis may include a transcript, pace, pauses, filler words, clarity, emphasis, and sampled presentation observations where supported. AI can be wrong; an administrator controls feedback, and it is not an official academic grade. Please remind your child not to share unnecessary sensitive information.</p><p><strong>Status: <?php echo !empty($mediaConsent['parent_granted'])?'Granted':'Not granted'; ?></strong></p><form action="parent-media-consent.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="consent_version" value="<?php echo e((string)$mediaConsent['version']); ?>"><input type="hidden" name="action" value="<?php echo !empty($mediaConsent['parent_granted'])?'withdraw':'grant'; ?>"><button class="button <?php echo !empty($mediaConsent['parent_granted'])?'ghost':'primary'; ?>" type="submit"><?php echo !empty($mediaConsent['parent_granted'])?'Withdraw permission':'Allow media AI coaching'; ?></button></form></article><?php endif; ?>
    </div>
  </section>

  <section class="parent-section" aria-labelledby="parent-recognition-title">
    <div class="parent-section-heading"><div><p class="eyebrow">Recognition</p><h2 id="parent-recognition-title">Achievements worth celebrating</h2><p>Only existing badges and certificate status are shown.</p></div></div>
    <div class="parent-recognition-grid">
      <article class="parent-card parent-badges-card"><div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">Earned Badges</p><h2><?php echo e((string) count($badges)); ?> earned</h2></div></div><?php if ($badges): ?><div class="badge-list parent-badge-list"><?php foreach ($badges as $badge): ?><span><?php echo e($badge); ?></span><?php endforeach; ?></div><?php else: ?><div class="parent-empty"><strong>No badges yet</strong><p>Verified badges will appear as milestones are completed.</p></div><?php endif; ?></article>
      <article class="parent-card parent-certificate-card"><div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">Certificate</p><h2><?php echo e($record['certificate_status'] ?? 'Not Ready'); ?></h2></div></div><p>View the student's current YUVA Club certificate record.</p><a class="button ghost" href="certificate.php?id=<?php echo e($studentId); ?>" target="_blank" rel="noopener">View Certificate</a></article>
      <article class="parent-card parent-rewards-card"><div class="parent-card-heading"><span class="parent-card-icon" aria-hidden="true"></span><div><p class="eyebrow">Participation</p><h2>Points and tokens</h2></div></div><dl class="parent-detail-list"><div><dt>Points</dt><dd><?php echo e((string) $points); ?></dd></div><div><dt>Tokens</dt><dd><?php echo e((string) $tokens); ?></dd></div><div><dt>Reward</dt><dd><?php echo e($record['reward_status'] ?? $rewardLevel); ?></dd></div></dl></article>
    </div>
  </section>

  <section class="parent-section" aria-labelledby="parent-announcements-title">
    <div class="parent-section-heading"><div><p class="eyebrow">YUVA Club Updates</p><h2 id="parent-announcements-title">Announcements</h2></div></div>
    <div class="parent-announcements"><?php if (trim($hub['announcements']) !== ''): ?><?php foreach (preg_split('/\R/', trim((string) $hub['announcements'])) ?: [] as $announcement): ?><?php if (trim($announcement) !== ''): ?><article><span aria-hidden="true"></span><p><?php echo e(trim($announcement)); ?></p></article><?php endif; ?><?php endforeach; ?><?php else: ?><div class="parent-empty"><strong>No announcements posted</strong><p>Updates from YUVA Club will appear here.</p></div><?php endif; ?></div>
  </section>

  <section class="parent-section" id="organization-membership" aria-labelledby="parent-organization-title">
    <div class="parent-section-heading"><div><p class="eyebrow">Organization permission</p><h2 id="parent-organization-title">Your child's organization connections</h2><p>A minor—or a student whose date of birth is unavailable—cannot activate an organization membership without parent or guardian approval.</p></div></div>
    <div class="parent-mentor-grid">
      <?php if ($organizationMemberships === []): ?><article class="parent-card"><p>No organization requests or memberships are currently recorded.</p></article><?php endif; ?>
      <?php foreach ($organizationMemberships as $membership): ?><article class="parent-card"><p class="eyebrow"><?php echo e((string) $membership['status']); ?></p><h3><?php echo e((string) $membership['organization_code']); ?></h3><p><?php echo e((string) $membership['invitation_purpose']); ?></p>
        <?php if (($membership['status'] ?? '') === 'ParentApprovalPending'): ?><form action="parent-organization-membership-action.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="membership_guid" value="<?php echo e((string) $membership['membership_guid']); ?>"><button class="button primary" name="decision" value="approve" type="submit">Approve</button> <button class="button ghost" name="decision" value="decline" type="submit">Decline</button></form><?php elseif (($membership['status'] ?? '') === 'Active'): ?><form action="parent-organization-membership-action.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="membership_guid" value="<?php echo e((string) $membership['membership_guid']); ?>"><button class="button ghost" name="decision" value="withdraw" type="submit">Withdraw permission</button></form><?php endif; ?>
      </article><?php endforeach; ?>
    </div>
  </section>

  <section class="parent-section parent-account-section" id="parent-account" aria-labelledby="parent-account-title">
    <article class="parent-card"><p class="eyebrow">Child plan</p><h2><?php echo e((string)$subscriptionStatus['display_name']); ?></h2><p><strong><?php echo e($studentId); ?></strong><?php if($subscriptionStatus['source_type']==='PREMIUM_BETA_PROMO'): ?><br>Source: YUVA Beta Program<?php if($subscriptionStatus['ends_at']!==null): ?><br>Valid until: <?php echo e((string)$subscriptionStatus['ends_at']); ?> UTC<?php endif; ?><?php endif; ?></p><p>Read-only subscription status. Plan management and payment are not available in this release.</p></article>
    <div><p class="eyebrow">Parent Account</p><h2 id="parent-account-title">Account and access</h2><p>Your access remains protected by the existing parent authentication and child-link authorization.</p></div>
    <a class="button ghost" href="portal-logout.php">Log Out</a>
  </section>
</main>
<?php portal_footer(); ?>
