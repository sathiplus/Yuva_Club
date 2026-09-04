<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/database.php';

const M20_DB = 'yuva_club_my_growth_phase2c2c_m20_rehearsal_20260903';
const M20_MARKER = 'P2C2C-M20-FUNCTIONAL';

final class M20ParentGuard
{
    /** @param array<string,mixed> $session */
    public function authorizedChildren(array &$session): ?array
    {
        return isset($session['allowed_yuva_id'])
            ? [['yuva_id' => $session['allowed_yuva_id']]]
            : null;
    }
}

function portal_parent_login_workflow(): M20ParentGuard
{
    static $guard;
    return $guard ??= new M20ParentGuard();
}

require_once dirname(__DIR__, 2) . '/backend/growth-profile.php';

function m20_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

/** @param array<string,mixed> $params */
function m20_id(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $id = $statement->fetchColumn();
    m20_assert($id !== false && (int) $id > 0, 'Synthetic identity was not returned.');
    return (int) $id;
}

function m20_count(PDO $pdo, string $table): int
{
    m20_assert((bool) preg_match('/^[a-z0-9_]+$/', $table), 'Unsafe table name.');
    return (int) $pdo->query('SELECT COUNT_BIG(*) FROM dbo.' . $table)->fetchColumn();
}

/** @return array<string,int> */
function m20_baseline(PDO $pdo): array
{
    $tables = ['users','students','parents','student_parents','organization_student_membership_requests',
        'quick_challenge_templates','quick_challenge_attempts','quick_challenge_evaluations',
        'student_challenge_personal_bests','presentation_submissions','presentation_verifications',
        'student_leadership_reflections','student_achievements','student_achievement_audit'];
    $result = [];
    foreach ($tables as $table) $result[$table] = m20_count($pdo, $table);
    return $result;
}

function m20_cleanup(PDO $pdo): void
{
    $students = $pdo->prepare("SELECT id,user_id FROM dbo.students WHERE yuva_id LIKE :marker");
    $students->execute(['marker' => M20_MARKER . '%']);
    $studentRows = $students->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $studentIds = array_map(static fn(array $r): int => (int) $r['id'], $studentRows);
    $userIds = array_map(static fn(array $r): int => (int) $r['user_id'], $studentRows);
    foreach ($studentIds as $student) {
        $pdo->prepare('DELETE FROM dbo.student_achievement_audit WHERE student_achievement_id IN(SELECT id FROM dbo.student_achievements WHERE student_id=:id)')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.student_achievements WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.presentation_verification_audit WHERE verification_id IN(SELECT id FROM dbo.presentation_verifications WHERE student_id=:id)')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.presentation_verifications WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.student_leadership_reflections WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.organization_membership_audit WHERE membership_id IN(SELECT id FROM dbo.organization_student_membership_requests WHERE student_id=:id)')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.organization_membership_tokens WHERE membership_id IN(SELECT id FROM dbo.organization_student_membership_requests WHERE student_id=:id)')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.organization_student_membership_requests WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.student_parents WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_evaluation_audit WHERE evaluation_id IN(SELECT id FROM dbo.quick_challenge_evaluations WHERE student_id=:id)')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_evaluations WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.student_challenge_personal_bests WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_attempts WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.competition_entries WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.presentation_submissions WHERE student_id=:id')->execute(['id'=>$student]);
        $pdo->prepare('DELETE FROM dbo.students WHERE id=:id')->execute(['id'=>$student]);
    }
    $parentUsers = $pdo->prepare("SELECT u.id,p.id parent_id FROM dbo.users u JOIN dbo.parents p ON p.user_id=u.id WHERE u.email LIKE :marker");
    $parentUsers->execute(['marker'=>'p2c2c-m20-functional-%@example.test']);
    foreach ($parentUsers->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $pdo->prepare('DELETE FROM dbo.student_parents WHERE parent_id=:id')->execute(['id'=>$row['parent_id']]);
        $pdo->prepare('DELETE FROM dbo.parents WHERE id=:id')->execute(['id'=>$row['parent_id']]);
        $pdo->prepare('DELETE FROM dbo.users WHERE id=:id')->execute(['id'=>$row['id']]);
    }
    foreach ($userIds as $user) $pdo->prepare('DELETE FROM dbo.users WHERE id=:id')->execute(['id'=>$user]);
    $competitions=$pdo->prepare('SELECT id FROM dbo.competitions WHERE created_by_email LIKE :marker');
    $competitions->execute(['marker'=>'p2c2c-m20-functional-%@example.test']);
    foreach (array_map('intval',$competitions->fetchAll(PDO::FETCH_COLUMN)) as $competition) {
        $pdo->prepare('DELETE FROM dbo.competition_audit WHERE competition_id=:id')->execute(['id'=>$competition]);
        $pdo->prepare('DELETE FROM dbo.competition_divisions WHERE competition_id=:id')->execute(['id'=>$competition]);
        $pdo->prepare('DELETE FROM dbo.competitions WHERE id=:id')->execute(['id'=>$competition]);
    }
    $templates=$pdo->prepare('SELECT id FROM dbo.quick_challenge_templates WHERE template_code LIKE :marker');
    $templates->execute(['marker'=>M20_MARKER.'%']);
    foreach (array_map('intval',$templates->fetchAll(PDO::FETCH_COLUMN)) as $template) {
        $pdo->prepare('DELETE FROM dbo.quick_challenge_template_version_skills WHERE template_version_id IN(SELECT id FROM dbo.quick_challenge_template_versions WHERE template_id=:id)')->execute(['id'=>$template]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_template_versions WHERE template_id=:id')->execute(['id'=>$template]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_templates WHERE id=:id')->execute(['id'=>$template]);
    }
    $pdo->prepare('DELETE FROM dbo.competition_rubric_versions WHERE rubric_code LIKE :marker')->execute(['marker'=>M20_MARKER.'%']);
    $pdo->prepare('DELETE FROM dbo.competition_division_versions WHERE division_code LIKE :marker')->execute(['marker'=>M20_MARKER.'%']);
}

/** @return array{id:int,user:int,yuva:string} */
function m20_student(PDO $pdo, string $suffix, int $level): array
{
    $email = 'p2c2c-m20-functional-' . strtolower($suffix) . '@example.test';
    $user = m20_id($pdo,"INSERT dbo.users(email,role,display_name,email_verified_at,[status]) OUTPUT INSERTED.id VALUES(:email,N'student',:name,SYSUTCDATETIME(),N'active')",['email'=>$email,'name'=>M20_MARKER.' '.$suffix]);
    $program = m20_id($pdo,'SELECT TOP(1)id FROM dbo.programs WHERE is_active=1 ORDER BY id');
    $yuva = M20_MARKER . '-' . strtoupper($suffix);
    $student = m20_id($pdo,"INSERT dbo.students(user_id,program_id,current_level_id,yuva_id,first_name,last_name,approval_status,approved_at) OUTPUT INSERTED.id VALUES(:user,:program,:level,:yuva,N'Synthetic',:suffix,N'approved',SYSUTCDATETIME())",['user'=>$user,'program'=>$program,'level'=>$level,'yuva'=>$yuva,'suffix'=>$suffix]);
    return ['id'=>$student,'user'=>$user,'yuva'=>$yuva];
}

/** @return array{template:int,version:int,entry:int,rubric:int,competition:int} */
function m20_challenge(PDO $pdo, int $student, string $yuva, string $suffix='MAIN'): array
{
    $marker=M20_MARKER.'-'.$suffix;
    $policy=m20_id($pdo,"SELECT TOP(1)id FROM dbo.quick_challenge_scoring_policies WHERE policy_code=N'impromptu-v1' AND [status]=N'Active'");
    $rubric=m20_id($pdo,"INSERT dbo.competition_rubric_versions(rubric_code,display_name,version_number,criteria_json,maximum_score) OUTPUT INSERTED.id VALUES(:code,:name,1,N'[]',100)",['code'=>$marker,'name'=>$marker]);
    $template=m20_id($pdo,"INSERT dbo.quick_challenge_templates(template_code,display_name,challenge_type,[status],created_by_email) OUTPUT INSERTED.id VALUES(:code,:name,N'impromptu',N'Published',:email)",['code'=>$marker,'name'=>$marker,'email'=>strtolower($marker).'@example.test']);
    $version=m20_id($pdo,"INSERT dbo.quick_challenge_template_versions(template_id,version_number,prompt_text,instructions,difficulty,preparation_seconds,response_seconds,maximum_attempts,attempt_policy,prompt_reveal_mode,rubric_version_id,ai_evaluation_enabled,scoring_policy_id) OUTPUT INSERTED.id VALUES(:template,1,N'Synthetic prompt',N'Synthetic only',N'Intermediate',0,120,20,N'best',N'visible',:rubric,1,:policy)",['template'=>$template,'rubric'=>$rubric,'policy'=>$policy]);
    $skill=m20_id($pdo,"SELECT TOP(1)id FROM dbo.quick_challenge_skills WHERE skill_code=N'leadership'");
    $pdo->prepare('INSERT dbo.quick_challenge_template_version_skills(template_version_id,skill_id,is_primary) VALUES(:version,:skill,1)')->execute(['version'=>$version,'skill'=>$skill]);
    $competition=m20_id($pdo,"INSERT dbo.competitions(title,[description],scope_type,[status],open_at,submission_deadline,rubric_version_id,created_by_email,quick_template_version_id,experience_mode) OUTPUT INSERTED.id VALUES(:name,N'Synthetic only',N'practice',N'Open',DATEADD(day,-30,SYSUTCDATETIME()),DATEADD(day,30,SYSUTCDATETIME()),:rubric,:email,:version,N'quick_practice')",['name'=>$marker,'rubric'=>$rubric,'email'=>strtolower($marker).'@example.test','version'=>$version]);
    $divisionVersion=m20_id($pdo,"INSERT dbo.competition_division_versions(division_code,display_name,version_number,min_age,max_age,eligibility_rule_json) OUTPUT INSERTED.id VALUES(:code,:name,1,8,21,N'{\"synthetic\":true}')",['code'=>$marker,'name'=>$marker]);
    $division=m20_id($pdo,'INSERT dbo.competition_divisions(competition_id,division_version_id) OUTPUT INSERTED.id VALUES(:competition,:division)',['competition'=>$competition,'division'=>$divisionVersion]);
    $entry=m20_id($pdo,"INSERT dbo.competition_entries(competition_id,competition_division_id,student_id,yuva_id,eligibility_snapshot_json) OUTPUT INSERTED.id VALUES(:competition,:division,:student,:yuva,N'{\"synthetic\":true}')",['competition'=>$competition,'division'=>$division,'student'=>$student,'yuva'=>$yuva]);
    return compact('template','version','entry','rubric','competition');
}

function m20_attempt(PDO $pdo,array $fixture,int $student,int $number,int $score,string $when,int $policyVersion=1,int $rubricVersion=1):int
{
    $snapshot=json_encode(['response_text'=>'Synthetic response '.$number],JSON_THROW_ON_ERROR);
    $hash=hash('sha256',$snapshot);
    $attempt=m20_id($pdo,"INSERT dbo.quick_challenge_attempts(competition_entry_id,student_id,template_version_id,attempt_number,[status],prompt_revealed_at,started_at,response_deadline_at,submitted_at,source_type,source_reference,source_revision_hash,source_snapshot_json) OUTPUT INSERTED.id VALUES(:entry,:student,:version,:number,N'Submitted',DATEADD(minute,-3,:at),DATEADD(minute,-2,:at2),DATEADD(minute,5,:at3),:at4,N'text_response',:marker,:hash,:snapshot)",['entry'=>$fixture['entry'],'student'=>$student,'version'=>$fixture['version'],'number'=>$number,'at'=>$when,'at2'=>$when,'at3'=>$when,'at4'=>$when,'marker'=>M20_MARKER,'hash'=>$hash,'snapshot'=>$snapshot]);
    $policy=m20_id($pdo,"SELECT TOP(1)id FROM dbo.quick_challenge_scoring_policies WHERE policy_code=N'impromptu-v1'");
    m20_id($pdo,"INSERT dbo.quick_challenge_evaluations(attempt_id,student_id,template_version_id,rubric_version_id,scoring_policy_id,source_revision_hash,source_type,template_version,rubric_version,scoring_policy_version,ai_provider,ai_model,prompt_version,[status],component_scores_json,total_score,coaching_feedback_json,benchmark_type,benchmark_score,benchmark_label,processing_started_at,completed_at) OUTPUT INSERTED.id VALUES(:attempt,:student,:version,:rubric,:policy,:hash,N'text_response',1,:rubric_version,:policy_version,N'rehearsal-fixture',N'none',N'fixture-v1',N'Completed',N'{\"Clarity\":80}',:score,N'{\"strengths\":[]}',N'Difficulty',80,N'Intermediate Benchmark',:at,:at2)",['attempt'=>$attempt,'student'=>$student,'version'=>$fixture['version'],'rubric'=>$fixture['rubric'],'policy'=>$policy,'hash'=>$hash,'rubric_version'=>$rubricVersion,'policy_version'=>$policyVersion,'score'=>$score,'at'=>$when,'at2'=>$when]);
    return $attempt;
}

function m20_main(): int
{
    m20_assert(getenv('YUVA_INTEGRATION_TEST_MODE')==='1','Explicit integration-test flag is required.');
    $pdo=Database::connection();
    m20_assert(db_driver_name($pdo)==='sqlsrv','PDO SQLSRV is required.');
    $db=(string)$pdo->query('SELECT DB_NAME()')->fetchColumn();
    m20_assert($db!=='yuva_club' && (str_contains($db,'_rehearsal_')||str_contains($db,'_test_')),'Production/non-test database refused.');
    m20_assert(hash_equals(M20_DB,$db),'Unexpected rehearsal database refused.');
    m20_cleanup($pdo);
    $baseline=m20_baseline($pdo);
    try {
        $explorer=m20_id($pdo,"SELECT id FROM dbo.levels WHERE code=N'explorer'");
        $leader=m20_id($pdo,"SELECT id FROM dbo.levels WHERE code=N'leader'");
        $empty=m20_student($pdo,'EMPTY',$explorer);
        $service=new GrowthProfileService($pdo);
        $emptyProfile=$service->forStudent($empty['yuva'],false);
        m20_assert($emptyProfile['trend']===[] && $emptyProfile['skills']===[] && $emptyProfile['summary']['challenges_completed']===0,'Honest empty state failed.');
        m20_assert(str_contains((string)$emptyProfile['next_action']['text'],'first Quick Challenge'),'Empty next action failed.');

        $student=m20_student($pdo,'FULL',$leader);
        $fixture=m20_challenge($pdo,$student['id'],$student['yuva']);
        $now=new DateTimeImmutable('now',new DateTimeZone('UTC'));
        $scores=[72,78,84];$attempts=[];
        foreach($scores as $i=>$score)$attempts[]=m20_attempt($pdo,$fixture,$student['id'],$i+1,$score,$now->modify('-'.(2-$i).' days')->format('Y-m-d H:i:s'));
        $incompatible=m20_attempt($pdo,$fixture,$student['id'],4,99,$now->modify('-4 days')->format('Y-m-d H:i:s'),2,2);
        for($i=5;$i<=10;$i++)m20_attempt($pdo,$fixture,$student['id'],$i,70+$i,$now->modify('-'.$i.' days')->format('Y-m-d H:i:s'));
        $pdo->prepare("INSERT dbo.student_challenge_personal_bests(student_id,template_id,score_version,best_attempt_id,best_score,achieved_at) VALUES(:student,:template,N'fixture-v1',:attempt,84,:at)")->execute(['student'=>$student['id'],'template'=>$fixture['template'],'attempt'=>$attempts[2],'at'=>$now->format('Y-m-d H:i:s')]);
        for($i=0;$i<4;$i++)$pdo->prepare("INSERT dbo.student_leadership_reflections(student_id,went_well,improve_next,learned,next_goal,created_at) VALUES(:student,N'Synthetic',N'Synthetic',N'Synthetic',N'Synthetic',:at)")->execute(['student'=>$student['id'],'at'=>$now->modify('-'.($i*7).' days')->format('Y-m-d H:i:s')]);
        $submission=m20_id($pdo,"INSERT dbo.presentation_submissions(student_id,research_notes,sources_used,presentation_outline,prepared_questions,[status]) OUTPUT INSERTED.id VALUES(:student,N'Synthetic',N'Synthetic',N'Synthetic',N'Synthetic',N'submitted')",['student'=>$student['id']]);
        $pdo->prepare("INSERT dbo.presentation_verifications(submission_id,student_id,[status],reviewer_email,reviewer_role,verification_note) VALUES(:submission,:student,N'Verified',N'p2c2c-m20-functional-admin@example.test',N'MasterAdmin',N'Synthetic only')")->execute(['submission'=>$submission,'student'=>$student['id']]);

        $profile=$service->forStudent($student['yuva'],true);
        m20_assert(count($profile['trend'])===5,'Trend is not bounded to five records.');
        $skill=$profile['skills'][0]??[];
        m20_assert((int)($skill['current']??-1)===84 && (int)($skill['previous']??-1)===78 && (int)($skill['change']??-99)===6,'Comparable 72/78/84 skill trend failed.');
        m20_assert((int)($skill['attempts']??0)===9,'Incompatible policy/rubric score was not excluded.');
        m20_assert(count($profile['personal_bests'])===1 && (int)$profile['personal_bests'][0]['best_score']===84,'Personal Best aggregation failed.');
        m20_assert($profile['summary']['benchmarks_beaten']>=1 && $profile['summary']['consistency_weeks']>=4,'Benchmark or weekly consistency failed.');
        m20_assert(count($profile['recent_activity'])<=12 && count($profile['trend'])<=5,'Bounded history contract failed.');
        m20_assert(isset($profile['summary'],$profile['skills'],$profile['benchmarks'],$profile['achievements'],$profile['ai_mentor'],$profile['next_action']),'Student view model incomplete.');
        m20_assert(count($profile['achievements'])===10,'All ten achievement rules were not issued.');
        $again=$service->forStudent($student['yuva'],true);
        m20_assert(count($again['achievements'])===10,'Achievement refresh deduplication failed.');

        $parentOk=['allowed_yuva_id'=>$student['yuva']];$parentBad=[];
        m20_assert($service->forParent($parentOk,$student['yuva'])['yuva_id']===$student['yuva'],'Linked Parent view failed.');
        $denied=false;try{$service->forParent($parentBad,$student['yuva']);}catch(RuntimeException){$denied=true;}m20_assert($denied,'Unrelated Parent was not rejected.');
        $membership=m20_id($pdo,"INSERT dbo.organization_student_membership_requests(organization_code,request_type,student_id,student_email_snapshot,student_email_normalized,invitation_purpose,[status],invited_by_email,expires_at) OUTPUT INSERTED.id VALUES(N'SYNTH-M20',N'LinkExisting',:student,N'synthetic@example.test',N'synthetic@example.test',N'Synthetic only',N'Active',N'p2c2c-m20-functional-admin@example.test',DATEADD(day,1,SYSUTCDATETIME()))",['student'=>$student['id']]);
        m20_assert($membership>0 && $service->forAdmin(['role'=>'OrganizationAdmin','organization_id'=>'SYNTH-M20'],$student['yuva'])['student_id']===$student['id'],'Same-organization authorization failed.');
        $denied=false;try{$service->forAdmin(['role'=>'OrganizationAdmin','organization_id'=>'OTHER'],$student['yuva']);}catch(RuntimeException){$denied=true;}m20_assert($denied,'Cross-organization access was not rejected.');
        m20_assert(count($service->definitions(['role'=>'MasterAdmin']))===10,'Master Admin definition inspection failed.');
        $metrics=$service->betaMetrics(['role'=>'MasterAdmin']);m20_assert(isset($metrics['students_attempted'],$metrics['students_improved'],$metrics['students_with_personal_best']),'Beta metrics failed.');

        $admin=m20_id($pdo,"INSERT dbo.users(email,role,display_name,email_verified_at,[status]) OUTPUT INSERTED.id VALUES(N'p2c2c-m20-functional-admin@example.test',N'admin',N'Synthetic Admin',SYSUTCDATETIME(),N'active')");
        $guid=(string)$profile['achievements'][0]['achievement_guid'];
        $service->revoke(['role'=>'MasterAdmin','id'=>$admin,'email'=>'p2c2c-m20-functional-admin@example.test'],$guid,'Synthetic corrective rehearsal');
        m20_assert((int)$pdo->query("SELECT COUNT_BIG(*) FROM dbo.student_achievements WHERE [status]=N'Revoked' AND student_id=".$student['id'])->fetchColumn()===1,'Corrective revocation failed.');
        $denied=false;try{$service->revoke(['role'=>'Student','id'=>$student['user']],$guid,'bad');}catch(RuntimeException){$denied=true;}m20_assert($denied,'Student corrective action was not rejected.');

        $before=m20_count($pdo,'student_achievement_audit');
        try{Database::transaction(function(PDO $tx):void{$tx->exec("INSERT dbo.student_achievement_audit(student_achievement_id,action_type,actor_role,succeeded,metadata_json) VALUES(NULL,N'ForcedRollback',N'System',0,N'{\"synthetic\":true}')");throw new RuntimeException('forced rollback');},'SERIALIZABLE',true);}catch(RuntimeException $e){m20_assert($e->getMessage()==='forced rollback','Unexpected rollback error.');}
        m20_assert(m20_count($pdo,'student_achievement_audit')===$before,'Forced rollback left partial audit state.');
        m20_assert(!array_key_exists('universal_score',$profile) && !array_key_exists('ai_provider',$profile),'Privacy/no-mystery-score contract failed.');
        fwrite(STDOUT,"PASS My Growth Phase 2C.2C SQLSRV functional rehearsal\n");
        return 0;
    } finally {
        m20_cleanup($pdo);
        foreach($baseline as $table=>$count)m20_assert(m20_count($pdo,$table)===$count,'Baseline count was not restored for '.$table.'.');
        m20_assert((int)$pdo->query('SELECT COUNT_BIG(*) FROM dbo.achievement_definitions')->fetchColumn()===10,'Seeded achievement definitions changed.');
    }
}

try { exit(m20_main()); }
catch(Throwable $error){$category=$error instanceof PDOException?'PDO/'.(string)$error->getCode():$error::class;$message=$error instanceof PDOException?'A database operation failed.':preg_replace('/[\r\n\t]+/',' ',trim($error->getMessage()));fwrite(STDERR,'FAIL '.$category.': '.$message."\n");exit(1);}
