<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/database.php';
require_once __DIR__ . '/../backend/ai/AiProvider.php';
require_once __DIR__ . '/../backend/ai/QuickChallengeScoring.php';
require_once __DIR__ . '/../backend/quick-challenge.php';
require_once __DIR__ . '/../backend/quick-challenge-evaluation.php';

use YuvaClub\AI\AiProvider;
use YuvaClub\AI\QuickChallengePromptCatalog;
use YuvaClub\AI\QuickChallengeScoreValidator;

const M19_FUNCTIONAL_DB = 'yuva_club_quick_scoring_phase2c2b_m19_rehearsal_20260902';
const M19_MARKER = 'p2c2b-m19-functional';

final class M19HarnessProvider implements AiProvider
{
    public int $calls = 0;

    public function __construct(private int $score = 80) {}
    public function providerName(): string { return 'rehearsal-fake'; }
    public function modelName(): string { return 'deterministic-v1'; }
    public function generateStructuredReview(string $prompt): array
    {
        $this->calls++;
        return ['ok' => true, 'output' => [
            'scores' => [
                'Claim' => $this->score,
                'Reasoning' => $this->score,
                'Evidence' => $this->score,
                'Persuasive Structure' => $this->score,
            ],
            'strengths' => ['Clear claim.'],
            'improvements' => ['Add one concrete example.', 'Tighten the conclusion.'],
            'practice_mission' => 'Add one sourced example and retry.',
        ]];
    }
}

function m19f_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

function m19f_id(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $value = $statement->fetchColumn();
    if ($value === false || (int) $value < 1) throw new RuntimeException('Synthetic fixture identity was not returned.');
    return (int) $value;
}

function m19f_attempt(PDO $pdo, int $entry, int $student, int $version, string $response, bool $validHash): string
{
    $snapshot = json_encode(['response_text' => $response], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $hash = $validHash ? hash('sha256', $snapshot) : str_repeat('a', 64);
    $statement = $pdo->prepare("INSERT dbo.quick_challenge_attempts
        (competition_entry_id,student_id,template_version_id,attempt_number,[status],prompt_revealed_at,started_at,response_deadline_at,submitted_at,source_type,source_reference,source_revision_hash,source_snapshot_json)
        OUTPUT CONVERT(NVARCHAR(36),INSERTED.attempt_guid) AS attempt_guid
        VALUES(:entry,:student,:version,(SELECT COUNT_BIG(*)+1 FROM dbo.quick_challenge_attempts WHERE competition_entry_id=:entry_count),N'Submitted',DATEADD(minute,-3,SYSUTCDATETIME()),DATEADD(minute,-2,SYSUTCDATETIME()),DATEADD(minute,5,SYSUTCDATETIME()),DATEADD(minute,-1,SYSUTCDATETIME()),N'text_response',:reference,:hash,:snapshot)");
    $statement->execute(['entry'=>$entry,'student'=>$student,'version'=>$version,'entry_count'=>$entry,'reference'=>M19_MARKER,'hash'=>$hash,'snapshot'=>$snapshot]);
    $guid = $statement->fetchColumn();
    if (!is_string($guid) || $guid === '') throw new RuntimeException('Synthetic attempt GUID was not returned.');
    return $guid;
}

function m19f_cleanup(PDO $pdo): void
{
    $template = $pdo->prepare('SELECT id FROM dbo.quick_challenge_templates WHERE template_code=:marker');
    $template->execute(['marker'=>M19_MARKER]);
    $templateId = $template->fetchColumn();
    $versionIds = [];
    if ($templateId !== false) {
        $ids = $pdo->prepare('SELECT id FROM dbo.quick_challenge_template_versions WHERE template_id=:template');
        $ids->execute(['template'=>$templateId]);
        $versionIds = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
    }
    $competitions = $pdo->prepare('SELECT id FROM dbo.competitions WHERE created_by_email=:marker');
    $competitions->execute(['marker'=>M19_MARKER.'@example.test']);
    $competitionIds = array_map('intval', $competitions->fetchAll(PDO::FETCH_COLUMN));
    foreach ($competitionIds as $competitionId) {
        $entries = $pdo->prepare('SELECT id FROM dbo.competition_entries WHERE competition_id=:id');
        $entries->execute(['id'=>$competitionId]);
        foreach (array_map('intval', $entries->fetchAll(PDO::FETCH_COLUMN)) as $entryId) {
            $attempts = $pdo->prepare('SELECT id FROM dbo.quick_challenge_attempts WHERE competition_entry_id=:id');
            $attempts->execute(['id'=>$entryId]);
            foreach (array_map('intval', $attempts->fetchAll(PDO::FETCH_COLUMN)) as $attemptId) {
                $pdo->prepare('DELETE FROM dbo.quick_challenge_evaluation_audit WHERE evaluation_id IN(SELECT id FROM dbo.quick_challenge_evaluations WHERE attempt_id=:id)')->execute(['id'=>$attemptId]);
                $pdo->prepare('DELETE FROM dbo.quick_challenge_evaluations WHERE attempt_id=:id')->execute(['id'=>$attemptId]);
                $pdo->prepare('DELETE FROM dbo.student_challenge_personal_bests WHERE best_attempt_id=:id')->execute(['id'=>$attemptId]);
            }
            $pdo->prepare('DELETE FROM dbo.quick_challenge_attempts WHERE competition_entry_id=:id')->execute(['id'=>$entryId]);
        }
        $pdo->prepare('DELETE FROM dbo.competition_audit WHERE competition_id=:id')->execute(['id'=>$competitionId]);
        $pdo->prepare('DELETE FROM dbo.competition_entries WHERE competition_id=:id')->execute(['id'=>$competitionId]);
        $pdo->prepare('DELETE FROM dbo.competition_divisions WHERE competition_id=:id')->execute(['id'=>$competitionId]);
        $pdo->prepare('DELETE FROM dbo.competitions WHERE id=:id')->execute(['id'=>$competitionId]);
    }
    $rubrics = [];
    if ($templateId !== false && $versionIds !== []) {
        $q=$pdo->prepare('SELECT rubric_version_id FROM dbo.quick_challenge_template_versions WHERE template_id=:id');$q->execute(['id'=>$templateId]);$rubrics=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
    }
    if ($templateId !== false) {
        $pdo->prepare('DELETE FROM dbo.quick_challenge_evaluation_audit WHERE entity_reference IN(SELECT CONVERT(NVARCHAR(36),version_guid) FROM dbo.quick_challenge_template_versions WHERE template_id=:id)')->execute(['id'=>$templateId]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_template_version_skills WHERE template_version_id IN(SELECT id FROM dbo.quick_challenge_template_versions WHERE template_id=:id)')->execute(['id'=>$templateId]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_template_versions WHERE template_id=:id')->execute(['id'=>$templateId]);
        $pdo->prepare('DELETE FROM dbo.quick_challenge_templates WHERE id=:id')->execute(['id'=>$templateId]);
    }
    foreach ($rubrics as $rubric) $pdo->prepare('DELETE FROM dbo.competition_rubric_versions WHERE id=:id')->execute(['id'=>$rubric]);
    $pdo->prepare('DELETE FROM dbo.competition_rubric_versions WHERE rubric_code=:marker')->execute(['marker'=>M19_MARKER]);
    $pdo->prepare('DELETE FROM dbo.competition_division_versions WHERE division_code=:marker')->execute(['marker'=>M19_MARKER]);
}

function m19f_main(): int
{
    $pdo = db();
    m19f_assert(db_driver_name($pdo)==='sqlsrv', 'SQLSRV is required.');
    m19f_assert(hash_equals(M19_FUNCTIONAL_DB,(string)$pdo->query('SELECT DB_NAME()')->fetchColumn()), 'Refusing non-rehearsal database target.');
    m19f_cleanup($pdo);
    $baseline = [];
    foreach (['quick_challenge_evaluations','quick_challenge_evaluation_audit','student_challenge_personal_bests','leadership_decisions','leadership_level_history','competition_submissions'] as $table) {
        $baseline[$table]=(int)$pdo->query('SELECT COUNT_BIG(*) FROM dbo.'.$table)->fetchColumn();
    }
    try {
        $student=$pdo->query("SELECT TOP(1)id,yuva_id FROM dbo.students WHERE approval_status=N'approved' ORDER BY id")->fetch(PDO::FETCH_ASSOC);
        m19f_assert(is_array($student), 'An approved baseline student is required.');
        $policy=m19f_id($pdo,"SELECT TOP(1)id FROM dbo.quick_challenge_scoring_policies WHERE policy_code=N'persuasion-v1' AND [status]=N'Active'");
        $rubric=m19f_id($pdo,"INSERT dbo.competition_rubric_versions(rubric_code,display_name,version_number,criteria_json,maximum_score) OUTPUT INSERTED.id VALUES(:code,N'M19 Rehearsal Rubric',1,N'[]',100)",['code'=>M19_MARKER]);
        $template=m19f_id($pdo,"INSERT dbo.quick_challenge_templates(template_code,display_name,challenge_type,[status],created_by_email) OUTPUT INSERTED.id VALUES(:code,N'M19 Functional',N'persuasion',N'Published',:email)",['code'=>M19_MARKER,'email'=>M19_MARKER.'@example.test']);
        $version=m19f_id($pdo,"INSERT dbo.quick_challenge_template_versions(template_id,version_number,prompt_text,instructions,difficulty,preparation_seconds,response_seconds,maximum_attempts,attempt_policy,prompt_reveal_mode,rubric_version_id,ai_evaluation_enabled,scoring_policy_id) OUTPUT INSERTED.id VALUES(:template,1,N'Explain your case.',N'Use evidence.',N'Intermediate',0,120,20,N'best',N'visible',:rubric,0,:policy)",['template'=>$template,'rubric'=>$rubric,'policy'=>$policy]);
        $competition=m19f_id($pdo,"INSERT dbo.competitions(title,[description],scope_type,owner_organization_code,[status],open_at,submission_deadline,rubric_version_id,created_by_email,quick_template_version_id,experience_mode) OUTPUT INSERTED.id VALUES(N'M19 Functional',N'Synthetic rehearsal only',N'organization',N'SYNTH-M19',N'Open',DATEADD(day,-1,SYSUTCDATETIME()),DATEADD(day,1,SYSUTCDATETIME()),:rubric,:email,:version,N'quick_practice')",['rubric'=>$rubric,'email'=>M19_MARKER.'@example.test','version'=>$version]);
        $divisionVersion=m19f_id($pdo,"INSERT dbo.competition_division_versions(division_code,display_name,version_number,min_age,max_age,eligibility_rule_json) OUTPUT INSERTED.id VALUES(:code,N'M19 All',1,8,21,N'{\"type\":\"synthetic\"}')",['code'=>M19_MARKER]);
        $division=m19f_id($pdo,'INSERT dbo.competition_divisions(competition_id,division_version_id) OUTPUT INSERTED.id VALUES(:competition,:division)',['competition'=>$competition,'division'=>$divisionVersion]);
        $entry=m19f_id($pdo,"INSERT dbo.competition_entries(competition_id,competition_division_id,student_id,yuva_id,eligibility_snapshot_json) OUTPUT INSERTED.id VALUES(:competition,:division,:student,:yuva,N'{\"synthetic\":true}')",['competition'=>$competition,'division'=>$division,'student'=>$student['id'],'yuva'=>$student['yuva_id']]);

        $provider=new M19HarnessProvider(80);
        $enabled=new QuickChallengeEvaluationService($pdo,$provider,new QuickChallengePromptCatalog(),new QuickChallengeScoreValidator(),static fn():bool=>true);
        $attempt=m19f_attempt($pdo,$entry,(int)$student['id'],$version,'A valid rehearsal response.',true);
        $disabled=$enabled->analyze((string)$student['yuva_id'],$attempt);
        m19f_assert(($disabled['status']??'')==='disabled'&&$provider->calls===0,'Disabled scoring invoked the provider.');

        $pdo->prepare('UPDATE dbo.quick_challenge_template_versions SET ai_evaluation_enabled=1 WHERE id=:id')->execute(['id'=>$version]);
        $blockedProvider=new M19HarnessProvider(80);
        $blocked=new QuickChallengeEvaluationService($pdo,$blockedProvider,new QuickChallengePromptCatalog(),new QuickChallengeScoreValidator(),static fn():bool=>false);
        m19f_assert(($blocked->analyze((string)$student['yuva_id'],$attempt)['status']??'')==='disabled'&&$blockedProvider->calls===0,'Non-entitled scoring was not blocked.');

        $mismatch=m19f_attempt($pdo,$entry,(int)$student['id'],$version,'Tamper-detection rehearsal response.',false);
        $before=$provider->calls;
        $rejected=false;
        try {
            $enabled->analyze((string)$student['yuva_id'],$mismatch);
        } catch (RuntimeException $error) {
            $rejected=str_contains($error->getMessage(),'source integrity validation failed');
        }
        m19f_assert($rejected,'Source hash mismatch was not rejected safely.');
        m19f_assert($provider->calls===$before,'Source hash mismatch reached the AI provider.');
        $evaluationCount=m19f_id($pdo,'SELECT COUNT_BIG(*)+1 FROM dbo.quick_challenge_evaluations e JOIN dbo.quick_challenge_attempts a ON a.id=e.attempt_id WHERE a.attempt_guid=:attempt',['attempt'=>$mismatch])-1;
        m19f_assert($evaluationCount===0,'Source hash mismatch reserved an evaluation.');
        $bestCount=m19f_id($pdo,'SELECT COUNT_BIG(*)+1 FROM dbo.student_challenge_personal_bests WHERE student_id=:student',['student'=>$student['id']])-1;
        m19f_assert($bestCount===0,'Source hash mismatch updated Personal Best.');
        fwrite(STDOUT,"PASS functional-preflight\n");
        return 0;
    } finally {
        m19f_cleanup($pdo);
        foreach ($baseline as $table=>$count) {
            m19f_assert((int)$pdo->query('SELECT COUNT_BIG(*) FROM dbo.'.$table)->fetchColumn()===$count,'Baseline count was not restored.');
        }
    }
}

try { exit(m19f_main()); }
catch (Throwable $error) {
    $category=$error instanceof PDOException?'PDO/'.(string)$error->getCode():$error::class;
    $message=$error instanceof PDOException?'A database operation failed.':preg_replace('/[\r\n\t]+/',' ',trim($error->getMessage()));
    fwrite(STDERR,'FAIL functional-preflight ['.$category.']: '.substr((string)$message,0,240).PHP_EOL);
    exit(1);
}
