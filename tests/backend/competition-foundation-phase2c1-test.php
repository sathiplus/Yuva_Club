<?php
declare(strict_types=1);
function competition_check(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$root=dirname(__DIR__,2);$migration=(string)file_get_contents($root.'/database/14-competition-foundation-phase2c1.azure-sql.sql');$rollback=(string)file_get_contents($root.'/database/14-competition-foundation-phase2c1-rollback.sql');$service=(string)file_get_contents($root.'/backend/competition.php');$admin=(string)file_get_contents($root.'/admin-competition-action.php');$join=(string)file_get_contents($root.'/student-competition-entry.php');$submit=(string)file_get_contents($root.'/student-competition-submit.php');$panel=(string)file_get_contents($root.'/competition-admin-panel.php');$portal=(string)file_get_contents($root.'/portal.php');
foreach(['competition_division_versions','competition_rubric_versions','competitions','competition_divisions','competition_entries','competition_submissions','competition_audit']as$table)competition_check(str_contains($migration,"dbo.$table"),'Migration 14 missing '.$table);
foreach(["N'Draft'","N'Scheduled'","N'Open'","N'SubmissionsClosed'","N'Archived'"]as$status)competition_check(str_contains($migration,$status),'Lifecycle missing '.$status);
foreach(['ROWVERSION','eligibility_snapshot_json','source_revision_hash','source_snapshot_json','uq_competition_entry_student_division','uq_competition_submission_entry']as$contract)competition_check(str_contains($migration,$contract),'Schema contract missing '.$contract);
competition_check(str_contains($migration,"OBJECT_ID(N'dbo.competitions', N'U') IS NULL")&&str_contains($migration,'sys.indexes'),'Migration 14 must be idempotent.');
competition_check(!str_contains($migration,'dbo.organizations'),'Migration 14 must not depend on skipped Migration 05.');
competition_check(str_contains($rollback,"DB_NAME() LIKE N'%rehearsal%'")&&!str_contains($rollback,"DB_NAME() = N'yuva_club'\nBEGIN"),'Rollback must be rehearsal-only.');
foreach(['MasterAdmin','OrganizationAdmin','Active same-organization membership is required','approval_status=N\'approved\'','Student is not eligible for this division','Challenge is not open for entries','already-entered','already-locked','CONVERT(BINARY(8),:version,2)','SERIALIZABLE','source_snapshot_json']as$contract)competition_check(str_contains($service,$contract),'Service contract missing '.$contract);
competition_check(str_contains($service,"hash('sha256',\$canonical)")&&str_contains($service,"'research_notes'")&&str_contains($service,"'file_sha256'"),'Locked source must be a canonical snapshot and SHA-256.');
competition_check(str_contains($service,'entry.student_id=:student'),'Student entry/source access must be ownership scoped.');
competition_check(str_contains($service,"competition.scope_type=N'organization' AND competition.owner_organization_code=:organization"),'Organization Admin views must be organization scoped.');
competition_check(!str_contains($service,'SELECT competition.*'),'Managed challenges must not expose a duplicate raw rowversion column.');
competition_check(str_contains($service,'CONVERT(VARCHAR(16),competition.row_version,2) AS row_version'),'Managed challenge rowversion must be selected as unambiguous hexadecimal text.');
competition_check(str_contains($service,"\$row['row_version']=normalize_sqlsrv_rowversion_token(\$row['row_version']??null)"),'Managed challenge rowversion must be validated at the PHP boundary.');
competition_check(str_contains($service,"normalize_sqlsrv_rowversion_token(\$created['row_version'])")&&str_contains($service,"bindValue(':version',\$version,PDO::PARAM_STR)"),'Challenge creation and lifecycle concurrency must share the canonical rowversion contract.');
foreach([$admin,$join,$submit]as$handler)competition_check(str_contains($handler,'verify_csrf_token')||str_contains($handler,'require_admin_post'),'Every mutation must enforce CSRF.');
competition_check(str_contains($admin,'[YUVA_ROLE_MASTER_ADMIN,YUVA_ROLE_ORGANIZATION_ADMIN]'),'Admin roles must be explicit.');
competition_check(str_contains($join,'require_student()')&&str_contains($submit,'require_student()'),'Student handlers require authenticated student context.');
competition_check(str_contains($panel,'PublicStudentIdentity::view')&&!preg_match('/email|phone|date_of_birth|parent/i',preg_replace('/created_by_email/', '', $panel)),'Entry roster must use safe public identity only.');
competition_check(str_contains($portal,'Submission Locked')&&str_contains($portal,'official competition submission will not change'),'Student locked-submission contract missing.');
competition_check(!str_contains($service,'leadership_decisions')&&!str_contains($service,'current_level_id=:target'),'Competition foundation must not promote leadership levels.');
echo "Competition foundation Phase 2C.1 contracts PASS\n";
