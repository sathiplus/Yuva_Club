<section class="form-card" id="challenges">
  <p class="eyebrow">Phase 2C.1</p><h2>Challenges</h2>
  <p>Create Practice or Organization challenges with a frozen division and rubric. Judging and results are not part of this release.</p>
  <?php if($competitionAdminNotice!==''): ?><div class="form-status success"><?php echo e($competitionAdminNotice); ?></div><?php endif; ?>
  <?php if($competitionAdminError!==''): ?><div class="form-status error" role="alert"><?php echo e($competitionAdminError); ?></div><?php endif; ?>
  <form action="admin-competition-action.php" method="post" class="form-card">
    <?php echo csrf_field(); ?><input type="hidden" name="action" value="create">
    <label>Challenge title<input name="title" maxlength="180" required></label>
    <label>Description<textarea name="description" maxlength="2000" required></textarea></label>
    <?php if(($admin['role']??'')===YUVA_ROLE_MASTER_ADMIN): ?>
      <label>Scope<select name="scope_type" required><option value="practice">Practice</option><option value="organization">Organization</option></select></label>
      <label>Organization code (required for Organization scope)<input name="organization_code" maxlength="120"></label>
    <?php else: ?><input type="hidden" name="scope_type" value="organization"><input type="hidden" name="organization_code" value="<?php echo e((string)$admin['organization_id']); ?>"><?php endif; ?>
    <label>Division<select name="division_code" required><option value="junior">Junior (8–12)</option><option value="senior">Senior (13–17)</option><option value="young-adult">Young Adult / College (18–21)</option></select></label>
    <div class="field-grid"><label>Opens at (UTC)<input type="datetime-local" name="open_at" required></label><label>Submission deadline (UTC)<input type="datetime-local" name="submission_deadline" required></label></div>
    <label>Rubric criteria JSON<textarea name="criteria_json" required>[{"name":"Content and research","weight":40},{"name":"Organization and clarity","weight":30},{"name":"Presentation evidence","weight":30}]</textarea></label>
    <label>Maximum score<input type="number" name="maximum_score" min="1" max="1000" value="100" required></label>
    <button class="button primary" type="submit">Create Draft Challenge</button>
  </form>
  <h3>Managed challenges</h3>
  <?php if($managedChallenges===[]): ?><p>No challenges are available.</p><?php endif; ?>
  <?php foreach($managedChallenges as $challenge): $next=['Draft'=>'Scheduled','Scheduled'=>'Open','Open'=>'SubmissionsClosed','SubmissionsClosed'=>'Archived'][(string)$challenge['status']]??null; ?>
    <article class="form-card"><h3><?php echo e((string)$challenge['title']); ?></h3><p><?php echo e((string)$challenge['scope_type']); ?><?php if(!empty($challenge['owner_organization_code'])): ?> · <?php echo e((string)$challenge['owner_organization_code']); ?><?php endif; ?> · <strong><?php echo e((string)$challenge['status']); ?></strong></p><p><?php echo e((string)$challenge['division_name']); ?> · <?php echo e((string)$challenge['rubric_name']); ?> · max <?php echo e((string)$challenge['maximum_score']); ?></p><p>Open: <?php echo e((string)$challenge['open_at']); ?> UTC<br>Deadline: <?php echo e((string)$challenge['submission_deadline']); ?> UTC</p>
    <?php if($next!==null): ?><form action="admin-competition-action.php" method="post"><?php echo csrf_field(); ?><input type="hidden" name="action" value="transition"><input type="hidden" name="competition_guid" value="<?php echo e((string)$challenge['competition_guid']); ?>"><input type="hidden" name="row_version" value="<?php echo e((string)$challenge['row_version']); ?>"><input type="hidden" name="target_status" value="<?php echo e($next); ?>"><button class="button ghost" type="submit">Move to <?php echo e($next); ?></button></form><?php endif; ?></article>
  <?php endforeach; ?>
  <h3>Scoped entries and locked submissions</h3>
  <?php if($managedChallengeEntries===[]): ?><p>No entries yet.</p><?php else: ?><div class="table-wrap"><table><thead><tr><th>Challenge</th><th>Safe YUVA identity</th><th>Division</th><th>Status</th></tr></thead><tbody><?php foreach($managedChallengeEntries as $entry): $identity=\YuvaClub\Identity\PublicStudentIdentity::view(['yuva_id'=>(string)$entry['yuva_id'],'public_handle'=>$entry['public_handle']??null,'avatar_code'=>$entry['avatar_code']??null]);$avatar=\YuvaClub\Identity\PublicStudentIdentity::avatar($identity['avatar_code']); ?><tr><td><?php echo e((string)$entry['title']); ?></td><td><?php echo e($avatar['icon'].' '.($identity['handle']?:$identity['yuva_id'])); ?><br><small><?php echo e($identity['yuva_id']); ?></small></td><td><?php echo e((string)$entry['division_name']); ?></td><td><?php echo e((string)$entry['entry_status']); ?><?php if(!empty($entry['submission_guid'])): ?><br><small>Submission Locked</small><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
