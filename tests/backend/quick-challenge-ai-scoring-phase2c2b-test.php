<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/backend/ai/QuickChallengeScoring.php';
use YuvaClub\AI\QuickChallengePromptCatalog;
use YuvaClub\AI\QuickChallengeScoreValidator;
function c2b(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$root=dirname(__DIR__,2);$migration=(string)file_get_contents($root.'/database/19-quick-challenge-ai-scoring-phase2c2b.azure-sql.sql');$rollback=(string)file_get_contents($root.'/database/19-quick-challenge-ai-scoring-phase2c2b-rollback.sql');$service=(string)file_get_contents($root.'/backend/quick-challenge-evaluation.php');$promptFile=(string)file_get_contents($root.'/backend/ai/QuickChallengeScoring.php');
$submit=(string)file_get_contents($root.'/student-quick-challenge-submit.php');
foreach(['quick_challenge_scoring_policies','quick_challenge_evaluations','quick_challenge_evaluation_audit','source_revision_hash','scoring_policy_version','ai_provider','ai_model','prompt_version','benchmark_score','ROWVERSION']as$item)c2b(str_contains($migration,$item),'Migration 19 missing '.$item);
c2b(str_contains($migration,"UNIQUE(attempt_id,source_revision_hash,scoring_policy_id)")&&str_contains($service,'db_acquire_application_lock'),'Duplicate and concurrent Analyze must be idempotent.');
c2b(str_contains($service,"['Failed','Stale']")&&str_contains($service,'ReprocessRequested'),'Failed/Stale reprocessing must be restricted and audited.');
c2b(str_contains($migration,"N'Processing',N'Completed',N'Failed',N'Stale'")&&str_contains($service,"[status]=N'Completed'"),'Safe evaluation lifecycle is required.');
c2b(str_contains($rollback,"DB_NAME() NOT LIKE N'%rehearsal%'")&&str_contains($rollback,'DROP TABLE dbo.quick_challenge_evaluation_audit'),'Rollback must be rehearsal/test-only and dependency ordered.');
c2b(str_contains($service,"'ai_quick_challenge_scoring'")&&str_contains($migration,'future-phase-3a3'),'Central entitlement integration and future usage attachment are required.');
c2b(str_contains($submit,'quick_challenge_evaluation_service()->analyze')&&str_contains($submit,'attempt submitted and locked'),'Submitted immutable attempts must enter AI evaluation without weakening submission persistence.');
c2b(!str_contains($service,'leadership_decisions')&&!str_contains($service,'current_level_id'),'AI practice scoring must not promote leadership.');
c2b(str_contains($service,"'audio_transcript','video_transcript','ai_mentor_delivery_review'")&&str_contains($service,"snapshot['timing']"),'Media attempts must reuse immutable transcript/timing evidence.');
c2b(str_contains($service,"['Beginner'=>65,'Intermediate'=>75,'Advanced'=>85]"),'Difficulty benchmarks must resolve deterministically.');
foreach(['accent','race','ethnicity','attractiveness','disability','socioeconomic','sensitive characteristic','coaching only','never an official Competition Score']as$guard)c2b(str_contains($promptFile,$guard),'Fairness guard missing '.$guard);
$validator=new QuickChallengeScoreValidator();$weights=['Clarity'=>60,'Structure'=>40];$valid=$validator->validate(['scores'=>['Clarity'=>80,'Structure'=>70],'strengths'=>['Clear main idea'],'improvements'=>['Use a stronger opening','Close with a summary'],'practice_mission'=>'State the main idea in the first sentence.'],$weights);
c2b(($valid['ok']??false)&&($valid['result']['total_score']??null)===76,'Weighted normalized score must be deterministic.');
c2b(!($validator->validate(['scores'=>['Clarity'=>101,'Structure'=>70],'strengths'=>['x'],'improvements'=>['y','z'],'practice_mission'=>'m'],$weights)['ok']??true),'Scores above 100 must be rejected.');
c2b(!($validator->validate(['scores'=>['Clarity'=>80],'strengths'=>['x'],'improvements'=>['y','z'],'practice_mission'=>'m'],$weights)['ok']??true),'Malformed AI JSON must be rejected.');
$prompt=(new QuickChallengePromptCatalog())->prompt('speech','Explain a local issue.','Student response',$weights);c2b(str_contains($prompt,'quick-challenge-score-v1')&&str_contains($prompt,'immutable youth practice response'),'Versioned challenge prompt is required.');
echo "PASS Phase 2C.2B AI scoring, benchmark, privacy, and safety contracts\n";
