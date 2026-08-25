<?php
declare(strict_types=1);

$root=dirname(__DIR__,2);
$service=file_get_contents($root.'/backend/presentation-verification.php');
$leadership=file_get_contents($root.'/backend/leadership.php');
$migration=file_get_contents($root.'/database/13-presentation-verification-phase2b.azure-sql.sql');
$studentRoute=file_get_contents($root.'/student-presentation-complete.php');
$adminRoute=file_get_contents($root.'/admin-presentation-verification.php');
$org=file_get_contents($root.'/organization-admin.php');
$admin=file_get_contents($root.'/admin.php');
foreach([$service,$leadership,$migration,$studentRoute,$adminRoute,$org,$admin] as $source)if($source===false)throw new RuntimeException('Required presentation verification source is missing.');
$assert=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
$assert(str_contains($service,'normalize_sqlsrv_rowversion_token')&&str_contains($service,"bindValue(':version',\$rowVersion,PDO::PARAM_STR)"),'Presentation rowversions must normalize and bind as canonical text.');

$assert(str_contains($migration,'presentation_verifications'),'Migration creates verification storage.');
$assert(str_contains($migration,'presentation_verification_audit'),'Migration preserves verification audit history.');
$assert(str_contains($migration,'ROWVERSION'),'Verification has optimistic concurrency.');
$assert(str_contains($migration,"WHERE [status]=N''Verified''"),'Only one active verification is allowed per submission.');
$assert(
    substr_count($migration, "EXEC(N'CREATE") === 4,
    'Indexes that reference newly added columns or tables use SQL Server-safe dynamic SQL.'
);
$assert(
    str_contains($migration, "EXEC(N'CREATE UNIQUE INDEX ux_presentation_submissions_student_revision"),
    'The source-revision index is compiled only after its guarded column addition executes.'
);
$assert(str_contains($service,"[status] IN(N'submitted',N'completed',N'reviewed')"),'Only completed/submitted presentations may be verified.');
$assert(str_contains($service,"[status]=N'Active'"),'Organization verification requires Active membership.');
$assert(str_contains($service,"Only Master Admin may revoke"),'Revocation authority is restricted.');
$assert(str_contains($service,"'presentation','human_review'"),'Verified presentation supplies one presentation and one human review source.');
$assert(str_contains($service,"already-verified"),'Duplicate verification is idempotent.');
$assert(substr_count($service,'db_acquire_application_lock')>=2,'Submission and verification races use transaction-scoped locks.');
$assert(str_contains($service,"source_revision_hash"),'Completed submission is deduplicated by source revision.');
$assert(str_contains($leadership,"dbo.presentation_verifications"),'Eligibility counts canonical verified presentations.');
$assert(!str_contains($leadership,"COUNT_BIG(DISTINCT id) FROM dbo.evaluations"),'Unverified rubric rows do not count as presentations.');
$assert(str_contains($studentRoute,'require_student()')&&str_contains($studentRoute,'verify_csrf_token'),'Student submission is authenticated and CSRF protected.');
$assert(str_contains($adminRoute,'require_admin_post')&&str_contains($adminRoute,'presentation_verification_service'),'Verification POST is human-authorized and CSRF protected.');
$assert(str_contains($org,'Verify Presentation')&&str_contains($admin,'Revoke Verification'),'Scoped and global UI controls exist.');
$assert(!str_contains($studentRoute,'verify('),'Student cannot self-verify.');

echo "Presentation verification Phase 2B contracts PASS\n";
