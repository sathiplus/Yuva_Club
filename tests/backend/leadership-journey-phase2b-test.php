<?php
declare(strict_types=1);

$root=dirname(__DIR__,2);
require_once $root.'/backend/leadership.php';
function leadership_check(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}

$migration=(string)file_get_contents($root.'/database/12-leadership-journey-phase2b.azure-sql.sql');
$rollback=(string)file_get_contents($root.'/database/12-leadership-journey-phase2b-rollback.sql');
$service=(string)file_get_contents($root.'/backend/leadership.php');
$student=(string)file_get_contents($root.'/portal.php');
$parent=(string)file_get_contents($root.'/parent.php');
$organization=(string)file_get_contents($root.'/organization-admin.php');
$admin=(string)file_get_contents($root.'/admin.php');

foreach(['leadership_rule_versions','leadership_evidence','student_leadership_reflections','leadership_eligibility_snapshots','leadership_decisions','leadership_level_history'] as $table){leadership_check(str_contains($migration,$table),'Migration 12 missing '.$table);}
leadership_check(str_contains($migration,"leadership-rules-v1"),'Versioned rules missing.');
leadership_check(str_contains($migration,"OBJECT_ID(N'dbo.leadership_rule_versions', N'U') IS NULL"),'Migration must be idempotent.');
leadership_check(str_contains($migration,'ROWVERSION'),'Optimistic concurrency missing.');
leadership_check(str_contains($migration,'tr_leadership_level_history_immutable'),'Immutable history trigger missing.');
leadership_check(str_contains($rollback,"DB_NAME() LIKE N'%rehearsal%'"),'Rollback must refuse non-rehearsal databases.');
leadership_check(!str_contains($migration,'04-')&&!str_contains($migration,'05-'),'Migration 12 must not introduce skipped migrations.');

$speaker=LeadershipEligibilityService::calculateRequirements(['presentations'=>2,'reviews'=>1,'reflections'=>1],['presentations'=>2,'reviews'=>1,'reflections'=>0]);
leadership_check(count(array_filter($speaker,fn(array $r):bool=>$r['complete']))===2,'Two presentations without reflection must not complete Speaker rules.');
$speaker=LeadershipEligibilityService::calculateRequirements(['presentations'=>2,'reviews'=>1,'reflections'=>1],['presentations'=>2,'reviews'=>1,'reflections'=>1]);
leadership_check(count(array_filter($speaker,fn(array $r):bool=>$r['complete']))===3,'Complete Speaker evidence must be eligible.');
$leader=LeadershipEligibilityService::calculateRequirements(['presentations'=>4,'reviews'=>2,'reflections'=>1,'leadership_service'=>1,'improvement'=>1],['presentations'=>4,'reviews'=>2,'reflections'=>1,'leadership_service'=>1,'improvement'=>1]);
leadership_check(count(array_filter($leader,fn(array $r):bool=>$r['complete']))===5,'Speaker to Leader rules failed.');
$mentor=LeadershipEligibilityService::calculateRequirements(['presentations'=>6,'reviews'=>3,'reflections_or_goal'=>1,'leadership_service'=>2,'peer_support'=>1,'improvement'=>1],['presentations'=>6,'reviews'=>3,'reflections_or_goal'=>1,'leadership_service'=>2,'peer_support'=>1,'improvement'=>1]);
leadership_check(count(array_filter($mentor,fn(array $r):bool=>$r['complete']))===6,'Leader to Mentor rules failed.');

foreach(["[status]=N'Applied'","[status]=N'Approved'",'current_level_id=:target','Active same-organization membership is required','Leadership promotion must advance exactly one level','Only Master Admin may override eligibility','source_revision','Concurrent leadership promotion was rejected','already-approved'] as $contract){leadership_check(str_contains($service,$contract),'Service security contract missing: '.$contract);}
leadership_check(!preg_match('/AiMentorService.{0,500}LeadershipApprovalService/s',$service),'AI Mentor must not invoke approval service.');
leadership_check(str_contains($student,'student-leadership-reflection.php')&&str_contains($student,'student-leadership-contribution.php'),'Student evidence workflows missing.');
leadership_check(str_contains($parent,'leadershipProgress')&&!str_contains($parent,'admin-leadership-decision.php'),'Parent must remain read-only.');
leadership_check(str_contains($organization,"[YUVA_ROLE_MASTER_ADMIN,YUVA_ROLE_ORGANIZATION_ADMIN]")===false,'Organization page must not broaden role checks.');
leadership_check(str_contains($organization,'Active students in this organization')||str_contains($organization,'Active students'),'Organization leadership scope explanation missing.');
leadership_check(str_contains($admin,'Audited Master Admin override'),'Master Admin override UI missing.');

foreach(['student-leadership-reflection.php','student-leadership-contribution.php'] as $handler){$body=(string)file_get_contents($root.'/'.$handler);leadership_check(str_contains($body,'require_student()')&&str_contains($body,'verify_csrf_token'),'Student handler security missing: '.$handler);}
foreach(['admin-leadership-decision.php','admin-leadership-evidence.php'] as $handler){$body=(string)file_get_contents($root.'/'.$handler);leadership_check(str_contains($body,'require_admin_post([YUVA_ROLE_MASTER_ADMIN,YUVA_ROLE_ORGANIZATION_ADMIN])'),'Admin handler role/CSRF security missing: '.$handler);}

echo "Leadership Journey Phase 2B contracts PASS\n";
