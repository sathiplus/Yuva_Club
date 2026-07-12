<?php
require __DIR__ . '/portal-lib.php';

$parentContext = require_parent_for_student($_GET['id'] ?? null);
$studentId = $parentContext['student_id'];
$student = $parentContext['student'];
$linkedStudents = $parentContext['students'];

$selection = read_json_file(topic_selections_file())[$studentId] ?? [];
$research = read_json_file(research_file())[$studentId] ?? [];
$record = student_record($studentId);
$hub = hub_settings();
$badges = earned_badges($record);
$points = student_points($record);
$tokens = student_tokens($record);
$rewardLevel = reward_level($record);
$rank = approved_rank($record);
$eligibleRank = rank_eligibility($record);
$challengeStage = challenge_stage($record);
$rubricScore = rubric_score($record);

portal_header('Parent Dashboard');
?>
<main>
  <section class="band">
    <div class="section-head">
      <p class="eyebrow">Parent Dashboard</p>
      <h1><?php echo e(student_display_name($student)); ?></h1>
      <p>View your student's Yuva Club progress and upcoming presentation details.</p>
      <p><a class="button ghost" href="portal-logout.php">Log Out</a></p>
      <?php if (count($linkedStudents) > 1): ?>
        <p>
          <?php foreach ($linkedStudents as $linkedStudentId => $linkedStudent): ?>
            <a class="button ghost" href="parent.php?id=<?php echo e($linkedStudentId); ?>"><?php echo e(student_display_name($linkedStudent)); ?></a>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>
    </div>
    <div class="portal-stat-grid">
      <div class="feature"><strong>Membership Group</strong><p><?php echo e(membership_group_label($student)); ?></p></div>
      <div class="feature"><strong>Approval</strong><p><?php echo e($record['approved'] ?? 'Pending'); ?></p></div>
      <div class="feature"><strong>Leadership Rank</strong><p><?php echo e($rank); ?></p></div>
      <div class="feature"><strong>Eligible Rank</strong><p><?php echo e($eligibleRank); ?></p></div>
      <div class="feature"><strong>Attendance</strong><p><?php echo e($record['attendance'] ?? '0'); ?> sessions</p></div>
      <div class="feature"><strong>Presentations</strong><p><?php echo e($record['presentations'] ?? '0'); ?></p></div>
      <div class="feature"><strong>Leadership Hours</strong><p><?php echo e($record['service_hours'] ?? '0'); ?></p></div>
      <div class="feature"><strong>Points</strong><p><?php echo e((string) $points); ?></p></div>
      <div class="feature"><strong>Tokens</strong><p><?php echo e((string) $tokens); ?></p></div>
      <div class="feature"><strong>Reward</strong><p><?php echo e($record['reward_status'] ?? $rewardLevel); ?></p></div>
      <div class="feature"><strong>Challenge Stage</strong><p><?php echo e($challengeStage); ?></p></div>
      <div class="feature"><strong>Rubric Score</strong><p><?php echo e((string) $rubricScore); ?> / 100</p></div>
      <div class="feature"><strong>Certificate</strong><p><?php echo e($record['certificate_status'] ?? 'Not Ready'); ?></p></div>
    </div>
  </section>
  <section class="band alt">
    <div class="section-head">
      <h2>Leadership Challenge Progress</h2>
      <p>Follow your student's program level, challenge stage, judging score, finalist status, and award readiness.</p>
    </div>
    <div class="portal-stat-grid">
      <div class="feature"><strong>Challenge Month</strong><p><?php echo e($record['challenge_month'] ?? date('Y-m')); ?></p></div>
      <div class="feature"><strong>Region</strong><p><?php echo e($record['challenge_region'] ?? 'Online'); ?></p></div>
      <div class="feature"><strong>Finalist Status</strong><p><?php echo e($record['finalist_status'] ?? 'Not Qualified'); ?></p></div>
      <div class="feature"><strong>Award Status</strong><p><?php echo e($record['award_status'] ?? 'None'); ?></p></div>
    </div>
    <div class="challenge-path">
      <?php foreach (challenge_stages() as $stage): ?>
        <span class="<?php echo $stage === $challengeStage ? 'active' : ''; ?>"><?php echo e($stage); ?></span>
      <?php endforeach; ?>
    </div>
    <p><a class="button ghost" href="leaderboard.php">View Challenge Leaderboard</a></p>
  </section>
  <section class="band">
    <div class="three-grid">
      <div class="form-card">
        <h2>Upcoming Presentation</h2>
        <p><strong><?php echo e($selection['topic_title'] ?? 'No topic selected'); ?></strong></p>
        <p><?php echo e($selection['presentation_date'] ?? ''); ?> <?php echo e($selection['presentation_time'] ?? ''); ?></p>
        <p><strong>Topic Status:</strong> <?php echo e($selection['status'] ?? 'Pending'); ?></p>
        <p><strong>Research Status:</strong> <?php echo e($research['status'] ?? 'Not Submitted'); ?></p>
      </div>
      <div class="form-card">
        <h2>Feedback</h2>
        <p><?php echo e($record['teacher_feedback'] ?? 'No feedback yet.'); ?></p>
        <p><strong>AI Feedback Summary:</strong> <?php echo e($record['ai_feedback_summary'] ?? 'No AI feedback yet.'); ?></p>
        <p><strong>Communication Skills:</strong> <?php echo e($record['communication_skills'] ?? 'Not recorded yet.'); ?></p>
        <p><strong>Leadership Milestones:</strong> <?php echo e($record['leadership_milestones'] ?? 'Not recorded yet.'); ?></p>
        <p><strong>Mentor Feedback:</strong> <?php echo e($record['mentor_feedback'] ?? 'No mentor feedback yet.'); ?></p>
        <p><strong>Rank Status:</strong> <?php echo e($record['rank_status'] ?? 'Approved'); ?></p>
        <p><strong>Score:</strong> <?php echo e($record['score'] ?? 'Optional'); ?></p>
      </div>
      <div class="form-card">
        <h2>Badges</h2>
        <?php if ($badges): ?><div class="badge-list"><?php foreach ($badges as $badge): ?><span><?php echo e($badge); ?></span><?php endforeach; ?></div><?php else: ?><p>No badges yet.</p><?php endif; ?>
      </div>
    </div>
  </section>
  <section class="band">
    <div class="two-grid">
      <div class="form-card">
        <h2>Certificates</h2>
        <p><a class="button ghost" href="certificate.php?id=<?php echo e($studentId); ?>" target="_blank">View Certificate</a></p>
      </div>
      <div class="form-card">
        <h2>Session Recordings</h2>
        <?php foreach (parse_link_lines($hub['recordings']) as $link): ?>
          <p><a href="<?php echo e($link['url']); ?>" target="_blank" rel="noopener"><?php echo e($link['title']); ?></a></p>
        <?php endforeach; ?>
        <?php if (trim($hub['recordings']) === ''): ?><p>No recordings posted yet.</p><?php endif; ?>
      </div>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
