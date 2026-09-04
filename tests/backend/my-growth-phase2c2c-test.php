<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);$migration=file_get_contents($root.'/database/20-my-growth-achievements-phase2c2c.azure-sql.sql');$rollback=file_get_contents($root.'/database/20-my-growth-achievements-phase2c2c-rollback.sql');$service=file_get_contents($root.'/backend/growth-profile.php');$student=file_get_contents($root.'/my-growth.php');$parent=file_get_contents($root.'/parent.php');$org=file_get_contents($root.'/organization-admin.php');$admin=file_get_contents($root.'/admin-growth-action.php');
function growth_check(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"FAIL $message\n");exit(1);}}
foreach(['achievement_definitions','student_achievements','student_achievement_audit','uq_student_achievement_once','ROWVERSION','rule_json','evidence_json']as$item)growth_check(str_contains($migration,$item),'Migration 20 missing '.$item);
growth_check(substr_count($migration,"(N'")>=10,'Ten meaningful definitions are seeded.');
growth_check(str_contains($rollback,"DB_NAME() NOT LIKE N'%rehearsal%'")&&str_contains($rollback,'SELECT 1 FROM dbo.student_achievements'),'Rollback is rehearsal-only and evidence-safe.');
foreach(['TOP(50)','TOP(12)','scoring_policy_version','rubric_version','template_id','TOP(20)','array_slice','consecutiveWeeks','practice_this_week','OPENJSON(:awards)','betaMetrics','aiMentorSummary','recentActivity']as$item)growth_check(str_contains($service,$item),'Bounded/compatible growth contract missing '.$item);
growth_check(!str_contains($service,'generateStructuredReview')&&!str_contains($student,'quick_challenge_evaluation_service()->analyze'),'My Growth render must make no AI call.');
growth_check(str_contains($service,"WITH(UPDLOCK,HOLDLOCK)")&&str_contains($service,"'SERIALIZABLE'"),'Achievement issuance must be transaction-safe and idempotent.');
growth_check(str_contains($service,'uq_student_achievement_once')||str_contains($migration,'uq_student_achievement_once'),'Duplicate achievement prevention missing.');
growth_check(str_contains($service,'forParent')&&str_contains($service,'authorizedChildren'),'Parent must use authoritative linked children.');
growth_check(str_contains($service,'forAdmin')&&str_contains($service,'Active same-organization membership'),'Organization scope guard missing.');
growth_check(str_contains($service,'l.code leadership_level'),'Growth Profile must resolve the current Leadership level from dbo.levels.code while preserving the leadership_level result key.');
growth_check(str_contains($service,"CONCAT(previous_level.code,N' to ',new_level.code)"),'Leadership activity labels must use the authoritative dbo.levels.code columns.');
growth_check(!str_contains($service,'.level_code'),'Growth Profile must not reference the nonexistent dbo.levels.level_code column.');
growth_check(str_contains($admin,'require_admin_post'),'Master corrective action must require authenticated CSRF-protected POST.');
growth_check(str_contains($student,'Complete another compatible challenge')&&str_contains($student,'No Personal Best yet'),'Honest one-score/empty states missing.');
growth_check(str_contains($parent,'forParent')&&str_contains($org,'forAdmin'),'Safe Parent and Organization summaries missing.');
growth_check(!str_contains($migration,'official_competition_score')&&!str_contains($migration,'winner')&&!str_contains($migration,'leaderboard'),'Migration must preserve product separation.');
echo "PASS Phase 2C.2C My Growth, achievement, privacy, and performance contracts\n";
