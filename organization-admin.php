<?php
declare(strict_types=1);

require __DIR__ . '/portal-lib.php';
$admin = require_admin([YUVA_ROLE_ORGANIZATION_ADMIN]);
$organizationId = normalize_organization_id((string) $admin['organization_id']);
if ($organizationId === YUVA_PLATFORM_ORGANIZATION_ID) {
    http_response_code(403);
    exit('Access denied.');
}

$students = array_filter(portal_students(), static fn(array $student): bool => student_organization_id($student) === $organizationId);
$membershipRequests = [];
try {
    $membershipRequests = organization_membership_service()->requestsForOrganization($organizationId);
    foreach ($membershipRequests as $membershipRequest) {
        if (($membershipRequest['status'] ?? '') !== 'Active') {
            continue;
        }
        $yuvaId = normalize_yuva_id((string) ($membershipRequest['yuva_id'] ?? ''));
        $linkedStudent = $yuvaId !== '' ? find_student($yuvaId) : null;
        if ($linkedStudent !== null) {
            $students[$yuvaId] = $linkedStudent;
        }
    }
} catch (Throwable $error) {
    error_log('YUVA organization membership dashboard unavailable correlation=' . bin2hex(random_bytes(12)) . ' exception_type=' . get_class($error));
}
$membershipNotice = (string) ($_SESSION['organization_membership_notice'] ?? '');
$membershipError = (string) ($_SESSION['organization_membership_error'] ?? '');
unset($_SESSION['organization_membership_notice'], $_SESSION['organization_membership_error']);
$records = read_json_file(portal_records_file());
$leadershipRows=[];
foreach($students as $leadershipStudentId=>$leadershipStudent){try{$leadershipRows[$leadershipStudentId]=['progress'=>leadership_eligibility_service()->latestByYuvaId((string)$leadershipStudentId),'evidence'=>leadership_eligibility_service()->evidence((string)$leadershipStudentId),'submissions'=>presentation_verification_service()->submissionsForStudent((string)$leadershipStudentId)];}catch(Throwable $error){error_log('YUVA organization leadership view unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}}
$leadershipAdminNotice=(string)($_SESSION['leadership_admin_notice']??'');$leadershipAdminError=(string)($_SESSION['leadership_admin_error']??'');unset($_SESSION['leadership_admin_notice'],$_SESSION['leadership_admin_error']);
$managedChallenges=[];$managedChallengeEntries=[];try{$managedChallenges=competition_foundation_service()->managedBy($admin);$managedChallengeEntries=competition_foundation_service()->managedEntries($admin);}catch(Throwable $error){error_log('YUVA organization challenges unavailable correlation='.bin2hex(random_bytes(12)).' exception_type='.get_class($error));}
$competitionAdminNotice=(string)($_SESSION['competition_admin_notice']??'');$competitionAdminError=(string)($_SESSION['competition_admin_error']??'');unset($_SESSION['competition_admin_notice'],$_SESSION['competition_admin_error']);
$reports = safety_reports();
$orgReports = array_filter($reports, static fn($report): bool => is_array($report)
    && student_organization_id(find_student((string) ($report['student_id'] ?? '')) ?? []) === $organizationId);

portal_header('Organization Admin Dashboard');
?>
<main>
  <section class="band">
    <div class="section-head"><p class="eyebrow">Organization Administrator Dashboard</p><h1><?php echo e($organizationId); ?></h1><p>Manage only the YUVA Club records assigned to this organization.</p><p><a class="button ghost" href="portal-logout.php">Log Out</a></p></div>
    <div class="dashboard-grid"><div class="metric-card"><span>Students</span><strong><?php echo count($students); ?></strong></div><div class="metric-card"><span>Safety Reports</span><strong><?php echo count($orgReports); ?></strong></div><div class="metric-card"><span>Role</span><strong><?php echo e($admin['role']); ?></strong></div></div>
    <section class="form-card" id="student-memberships">
      <p class="eyebrow">Secure student onboarding</p><h2>Invite or request a student connection</h2>
      <p>Students control their own accounts. A membership becomes active only after the student accepts and, for a minor or missing date of birth, a linked parent or guardian approves.</p>
      <?php if ($membershipNotice !== ''): ?><div class="form-status success"><?php echo e($membershipNotice); ?></div><?php endif; ?>
      <?php if ($membershipError !== ''): ?><div class="form-status error" role="alert"><?php echo e($membershipError); ?></div><?php endif; ?>
      <div class="dashboard-grid">
        <form action="organization-student-request.php" method="post" class="form-card">
          <?php echo csrf_field(); ?><input type="hidden" name="request_type" value="InviteNew">
          <h3>Invite a new student</h3>
          <label>Student first name <input name="student_first_name" required maxlength="120"></label>
          <label>Student last name <input name="student_last_name" maxlength="120"></label>
          <label>Student email <input name="student_email" type="email" required maxlength="190"></label>
          <label>Parent/guardian email <input name="parent_email" type="email" maxlength="190"></label>
          <label>Grade or cohort label <input name="cohort_label" maxlength="120"></label>
          <label>Invitation purpose <input name="invitation_purpose" required maxlength="220" value="Join our YUVA Club program"></label>
          <label>Optional message <textarea name="invitation_message" maxlength="1000"></textarea></label>
          <button class="button primary" type="submit">Send Secure Invitation</button>
        </form>
        <form action="organization-student-request.php" method="post" class="form-card">
          <?php echo csrf_field(); ?><input type="hidden" name="request_type" value="LinkExisting">
          <h3>Request an existing student link</h3>
          <p>Use a YUVA ID or verified student email. The response is intentionally neutral to protect student privacy.</p>
          <label>YUVA ID or student email <input name="student_identifier" required maxlength="190"></label>
          <label>Grade or cohort label <input name="cohort_label" maxlength="120"></label>
          <label>Request purpose <input name="invitation_purpose" required maxlength="220" value="Connect with our organization"></label>
          <label>Optional message <textarea name="invitation_message" maxlength="1000"></textarea></label>
          <button class="button primary" type="submit">Send Link Request</button>
        </form>
      </div>
      <h3>Pending, active, and closed memberships</h3>
      <?php if ($membershipRequests === []): ?><p>No secure membership requests yet.</p><?php else: ?>
      <div class="table-wrap"><table><thead><tr><th>Student</th><th>Type</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody>
      <?php foreach ($membershipRequests as $request): ?><tr>
        <td><?php echo e(trim((string) ($request['student_first_name'] ?? '') . ' ' . (string) ($request['student_last_name'] ?? '')) ?: 'Student request'); ?><?php if (!empty($request['yuva_id'])): ?><br><small><?php echo e((string) $request['yuva_id']); ?></small><?php endif; ?></td>
        <td><?php echo e((string) $request['request_type']); ?></td><td><?php echo e((string) $request['status']); ?></td><td><?php echo e(display_eastern_time((string) $request['created_at'])); ?></td>
        <td><?php if (!in_array((string) $request['status'], ['Archived','Removed'], true)): ?><form action="organization-student-archive.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="membership_guid" value="<?php echo e((string) $request['membership_guid']); ?>"><button class="button ghost" type="submit"><?php echo ($request['status'] ?? '') === 'Active' ? 'Remove' : 'Archive'; ?></button></form><?php endif; ?></td>
      </tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </section>
    <section class="form-card" id="leadership-reviews"><p class="eyebrow">Human approval</p><h2>Leadership Reviews</h2><p>Eligibility never changes a student's approved level. You may decide only for Active students in this organization.</p><?php if($leadershipAdminNotice!==''): ?><div class="form-status success"><?php echo e($leadershipAdminNotice); ?></div><?php endif; ?><?php if($leadershipAdminError!==''): ?><div class="form-status error"><?php echo e($leadershipAdminError); ?></div><?php endif; ?>
      <?php if($leadershipRows===[]): ?><p>No scoped leadership records are available.</p><?php else: ?><?php foreach($leadershipRows as $leadershipStudentId=>$leadershipRow): $progress=$leadershipRow['progress'];$identity=public_student_identity((string)$leadershipStudentId);$avatar=\YuvaClub\Identity\PublicStudentIdentity::avatar($identity['avatar_code']); ?><article class="form-card"><h3><?php echo e($avatar['icon'].' '.($identity['handle']?:$leadershipStudentId)); ?> <small><?php echo e((string)$leadershipStudentId); ?></small></h3><p>Current: <strong><?php echo e((string)$progress['current_level']); ?></strong> · Next: <strong><?php echo e((string)($progress['target_level']??'None')); ?></strong> · Status: <strong><?php echo e((string)$progress['status']); ?></strong></p><ul><?php foreach($progress['requirements'] as $requirement): ?><li><?php echo !empty($requirement['complete'])?'✓':'○'; ?> <?php echo e((string)$requirement['label']); ?> (<?php echo e((string)$requirement['actual']); ?>/<?php echo e((string)$requirement['required']); ?>)</li><?php endforeach; ?></ul>
        <h4>Completed presentations</h4><?php if(($leadershipRow['submissions']??[])===[]): ?><p>No completed presentation submissions are awaiting review.</p><?php else: ?><?php foreach($leadershipRow['submissions'] as $submission): ?><article><p>Presentation <?php echo e((string)$submission['id']); ?> · <?php echo e((string)($submission['verification_status']??'Awaiting Human Verification')); ?></p><?php if(empty($submission['verification_guid'])): ?><form action="admin-presentation-verification.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="yuva_id" value="<?php echo e((string)$leadershipStudentId); ?>"><input type="hidden" name="submission_id" value="<?php echo e((string)$submission['id']); ?>"><label>Optional verification note<textarea name="note" maxlength="2000"></textarea></label><button class="button primary" type="submit">Verify Presentation</button></form><?php endif; ?></article><?php endforeach; ?><?php endif; ?>
        <?php foreach($leadershipRow['evidence'] as $evidence): if(($evidence['status']??'')!=='Pending')continue; ?><form action="admin-leadership-evidence.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="yuva_id" value="<?php echo e((string)$leadershipStudentId); ?>"><input type="hidden" name="evidence_guid" value="<?php echo e((string)$evidence['evidence_guid']); ?>"><p><strong><?php echo e((string)$evidence['evidence_type']); ?></strong> · <?php echo e((string)$evidence['evidence_date']); ?> — <?php echo e((string)($evidence['notes']??'')); ?></p><label>Review note<input name="reason" maxlength="1000"></label><button class="button primary" name="decision" value="Approved">Approve Evidence</button> <button class="button ghost" name="decision" value="Rejected">Reject Evidence</button></form><?php endforeach; ?>
        <form action="admin-leadership-evidence.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="add"><input type="hidden" name="yuva_id" value="<?php echo e((string)$leadershipStudentId); ?>"><h4>Record verified evidence</h4><label>Evidence type<select name="evidence_type"><option value="improvement">Demonstrated improvement</option><option value="leadership_service">Leadership/service contribution</option><option value="peer_support">Peer support/mentoring</option><option value="human_review">Human presentation review</option><option value="leadership_goal">Leadership goal</option></select></label><label>Evidence date<input type="date" name="evidence_date" required></label><label>Observation and reason<textarea name="notes" required maxlength="2000"></textarea></label><button class="button ghost" type="submit">Add Verified Evidence</button></form>
        <?php if(($progress['target_level_id']??null)!==null): ?><form action="admin-leadership-decision.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="yuva_id" value="<?php echo e((string)$leadershipStudentId); ?>"><input type="hidden" name="snapshot_id" value="<?php echo e((string)$progress['snapshot_id']); ?>"><input type="hidden" name="row_version" value="<?php echo e((string)$progress['row_version']); ?>"><input type="hidden" name="source_revision" value="<?php echo e((string)$progress['source_revision']); ?>"><label>Decision note<textarea name="reason" required maxlength="2000"></textarea></label><button class="button primary" name="decision" value="Approved" <?php echo $progress['status']==='Eligible for Review'?'':'disabled'; ?>>Approve <?php echo e((string)$progress['target_level']); ?></button> <button class="button ghost" name="decision" value="More Evidence Needed">More Evidence Needed</button></form><?php endif; ?></article><?php endforeach; ?><?php endif; ?>
    </section>
    <?php require __DIR__.'/competition-admin-panel.php'; ?>
    <section class="form-card"><h2>Organization Students</h2>
      <?php if ($students === []): ?><p>No student records are assigned to this organization yet.</p>
      <?php else: ?><div class="table-wrap"><table><thead><tr><th>YUVA Identity</th><th>Name</th><th>Program</th><th>School</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($students as $studentId => $student): $studentRecord = $records[$studentId] ?? []; $identity=public_student_identity((string)$studentId);$avatar=\YuvaClub\Identity\PublicStudentIdentity::avatar($identity['avatar_code']); ?><tr><td><strong><?php echo e($avatar['icon'].' '.($identity['handle']?:$studentId)); ?></strong><br><small><?php echo e((string)$studentId); ?></small></td><td><?php echo e(student_display_name($student)); ?></td><td><?php echo e((string) ($student['Program Group'] ?? '')); ?></td><td><?php echo e((string) ($student['School'] ?? '')); ?></td><td><?php echo e((string) ($studentRecord['approved'] ?? 'Pending')); ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </section>
  </section>
</main>
<?php portal_footer(); ?>
