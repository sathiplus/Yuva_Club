<?php
require __DIR__ . '/portal-lib.php';

$isAdmin = ($_SESSION['admin_logged_in'] ?? false) === true;
$isStudent = logged_in_student_id() !== null;
$isParent = normalize_yuva_id($_SESSION['parent_student_id'] ?? '') !== '';
$canViewStudentIds = $isAdmin || $isStudent || $isParent;

$programFilter = clean_text($_GET['program'] ?? '');
$stageFilter = clean_text($_GET['stage'] ?? '');
$students = portal_students();
$rows = [];

foreach ($students as $student) {
    $studentId = normalize_yuva_id($student['Yuva Club ID'] ?? '');
    if ($studentId === '') {
        continue;
    }
    $record = student_record($studentId);
    if (($record['approved'] ?? 'Pending') !== 'Approved') {
        continue;
    }

    $program = membership_group_label($student);
    $stage = challenge_stage($record);
    if ($programFilter !== '' && $programFilter !== $program) {
        continue;
    }
    if ($stageFilter !== '' && $stageFilter !== $stage) {
        continue;
    }

    $rows[] = [
        'student_id' => $studentId,
        'name' => student_display_name($student),
        'program' => $program,
        'rank' => approved_rank($record),
        'stage' => $stage,
        'points' => student_points($record),
        'tokens' => student_tokens($record),
        'rubric' => rubric_score($record),
        'finalist_status' => $record['finalist_status'] ?? 'Not Qualified',
        'award_status' => $record['award_status'] ?? 'None',
    ];
}

usort($rows, function (array $a, array $b): int {
    return [$b['points'], $b['rubric'], $a['name']] <=> [$a['points'], $a['rubric'], $b['name']];
});

$programs = array_values(array_unique(array_map(fn($student) => membership_group_label($student), $students)));
sort($programs);

portal_header('Challenge Leaderboard');
?>
<main>
  <section class="band">
    <div class="section-head">
      <p class="eyebrow">Leadership Challenge</p>
      <h1>Challenge Leaderboard</h1>
      <p>Track approved student progress by program, challenge stage, points, tokens, and presentation rubric score.</p>
    </div>
    <form class="form-card" method="get">
      <div class="field-grid">
        <div class="field">
          <label for="program">Program</label>
          <select id="program" name="program">
            <option value="">All programs</option>
            <?php foreach ($programs as $program): ?>
              <option value="<?php echo e($program); ?>" <?php echo $programFilter === $program ? 'selected' : ''; ?>><?php echo e($program); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="stage">Challenge Stage</label>
          <select id="stage" name="stage">
            <option value="">All stages</option>
            <?php foreach (challenge_stages() as $stage): ?>
              <option value="<?php echo e($stage); ?>" <?php echo $stageFilter === $stage ? 'selected' : ''; ?>><?php echo e($stage); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <button class="button primary" type="submit">Apply Filters</button>
    </form>
  </section>

  <section class="band">
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Rank</th>
            <th>Student</th>
            <th>Program</th>
            <th>Level</th>
            <th>Stage</th>
            <th>Points</th>
            <th>Tokens</th>
            <th>Rubric</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $index => $row): ?>
            <tr>
              <td><?php echo e((string) ($index + 1)); ?></td>
              <td>
                <?php echo e($row['name']); ?>
                <?php if ($canViewStudentIds): ?>
                  <br><small><?php echo e($row['student_id']); ?></small>
                <?php endif; ?>
              </td>
              <td><?php echo e($row['program']); ?></td>
              <td><?php echo e($row['rank']); ?></td>
              <td><?php echo e($row['stage']); ?></td>
              <td><?php echo e((string) $row['points']); ?></td>
              <td><?php echo e((string) $row['tokens']); ?></td>
              <td><?php echo e((string) $row['rubric']); ?> / 100</td>
              <td><?php echo e($row['finalist_status']); ?><br><small><?php echo e($row['award_status']); ?></small></td>
            </tr>
          <?php endforeach; ?>
          <?php if ($rows === []): ?>
            <tr><td colspan="9">No approved leaderboard records match these filters yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
<?php portal_footer(); ?>
