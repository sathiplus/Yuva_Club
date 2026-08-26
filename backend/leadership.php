<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

final class LeadershipEligibilityService
{
    public const RULE_VERSION = 'leadership-rules-v1';

    public function __construct(private readonly PDO $pdo)
    {
        if (!db_is_sqlsrv($pdo)) {
            throw new RuntimeException('Leadership Journey requires Azure SQL.');
        }
    }

    public function evaluateByYuvaId(string $yuvaId, bool $persist = true): array
    {
        $student = $this->student($yuvaId);
        $rule = $this->rule((int) $student['current_level_id']);
        if ($rule === null) {
            return $this->terminalProgress($student);
        }

        $requirements = json_decode((string) $rule['rules_json'], true, 16, JSON_THROW_ON_ERROR);
        $this->syncSystemEvidence((int) $student['id']);
        $counts = $this->evidenceCounts((int) $student['id'], $requirements);
        $checks = self::calculateRequirements($requirements, $counts);
        $complete = count(array_filter($checks, static fn(array $item): bool => $item['complete']));
        $status = $complete === count($checks) ? 'Eligible for Review' : 'Building Evidence';
        $snapshot = [
            'student_id' => (int) $student['id'],
            'yuva_id' => (string) $student['yuva_id'],
            'current_level' => (string) $student['current_level'],
            'current_level_id' => (int) $student['current_level_id'],
            'target_level' => (string) $rule['target_level'],
            'target_level_id' => (int) $rule['level_to_id'],
            'rule_version' => self::RULE_VERSION,
            'status' => $status,
            'requirements' => $checks,
            'completed' => $complete,
            'required' => count($checks),
        ];
        $json = json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $revision = hash('sha256', $json);
        $snapshot['source_revision'] = $revision;
        if ($persist) {
            $stmt = $this->pdo->prepare(
                "INSERT dbo.leadership_eligibility_snapshots(student_id,current_level_id,target_level_id,rule_version,[status],evidence_snapshot,source_revision)
                 OUTPUT INSERTED.id, INSERTED.row_version AS row_version
                 VALUES(:student,:current,:target,:version,:status,:evidence,:revision)"
            );
            $stmt->execute(['student'=>(int)$student['id'],'current'=>(int)$student['current_level_id'],'target'=>(int)$rule['level_to_id'],'version'=>self::RULE_VERSION,'status'=>$status,'evidence'=>$json,'revision'=>$revision]);
            $saved = $stmt->fetch();
            $snapshot['snapshot_id'] = (int) $saved['id'];
            $snapshot['row_version'] = normalize_sqlsrv_rowversion_token($saved['row_version'] ?? null);
        }
        return $snapshot;
    }

    public function latestByYuvaId(string $yuvaId): array
    {
        $student = $this->student($yuvaId);
        $stmt = $this->pdo->prepare(
            "SELECT TOP (1) snapshot.*, current_level.name current_level, target_level.name target_level,
                    snapshot.row_version
             FROM dbo.leadership_eligibility_snapshots snapshot
             JOIN dbo.levels current_level ON current_level.id=snapshot.current_level_id
             LEFT JOIN dbo.levels target_level ON target_level.id=snapshot.target_level_id
             WHERE snapshot.student_id=:student ORDER BY snapshot.id DESC"
        );
        $stmt->execute(['student'=>(int)$student['id']]);
        $row=$stmt->fetch();
        if (!is_array($row)) return $this->evaluateByYuvaId($yuvaId);
        $evidence=json_decode((string)$row['evidence_snapshot'],true) ?: [];
        return array_merge($evidence,['snapshot_id'=>(int)$row['id'],'status'=>(string)$row['status'],'row_version'=>normalize_sqlsrv_rowversion_token($row['row_version'] ?? null)]);
    }

    public function addReflection(string $yuvaId, array $input): string
    {
        $student=$this->student($yuvaId);
        $values=[];
        foreach (['went_well','improve_next','learned','next_goal'] as $field) {
            $values[$field]=trim((string)($input[$field]??''));
            if ($values[$field]==='' || mb_strlen($values[$field])>1500) throw new InvalidArgumentException('Complete each reflection prompt.');
        }
        return $this->transaction(function() use($student,$values,$input): string {
            $stmt=$this->pdo->prepare("INSERT dbo.student_leadership_reflections(student_id,presentation_submission_id,went_well,improve_next,learned,next_goal) OUTPUT CONVERT(NVARCHAR(36),INSERTED.reflection_guid) VALUES(:student,:submission,:well,:improve,:learned,:goal)");
            $stmt->execute(['student'=>(int)$student['id'],'submission'=>isset($input['submission_id'])?(int)$input['submission_id']:null,'well'=>$values['went_well'],'improve'=>$values['improve_next'],'learned'=>$values['learned'],'goal'=>$values['next_goal']]);
            $guid=(string)$stmt->fetchColumn();
            $e=$this->pdo->prepare("INSERT dbo.leadership_evidence(student_id,evidence_type,source_type,source_id,[status],evidence_date,notes) VALUES(:student,N'reflection',N'student_reflection',:source,N'Approved',CONVERT(date,SYSUTCDATETIME()),:notes)");
            $e->execute(['student'=>(int)$student['id'],'source'=>$guid,'notes'=>$values['next_goal']]);
            return $guid;
        });
    }

    public function submitContribution(string $yuvaId, array $input): string
    {
        $student=$this->student($yuvaId);
        $type=(string)($input['evidence_type']??'leadership_service');
        if (!in_array($type,['leadership_service','peer_support'],true)) throw new InvalidArgumentException('Unsupported contribution type.');
        $title=trim((string)($input['title']??'')); $notes=trim((string)($input['description']??''));
        $date=trim((string)($input['evidence_date']??''));
        if ($title==='' || $notes==='' || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) throw new InvalidArgumentException('Complete the contribution details.');
        $source=bin2hex(random_bytes(16));
        $stmt=$this->pdo->prepare("INSERT dbo.leadership_evidence(student_id,evidence_type,source_type,source_id,[status],evidence_date,notes,metadata_json) VALUES(:student,:type,N'student_contribution',:source,N'Pending',:date,:notes,:metadata)");
        $stmt->execute(['student'=>(int)$student['id'],'type'=>$type,'source'=>$source,'date'=>$date,'notes'=>$notes,'metadata'=>json_encode(['title'=>$title,'hours'=>max(0,(float)($input['hours']??0))],JSON_THROW_ON_ERROR)]);
        return $source;
    }

    public function history(string $yuvaId): array
    {
        $student=$this->student($yuvaId);
        $stmt=$this->pdo->prepare("SELECT previous_level.name previous_level,new_level.name new_level,history.promoted_at FROM dbo.leadership_level_history history JOIN dbo.levels previous_level ON previous_level.id=history.previous_level_id JOIN dbo.levels new_level ON new_level.id=history.new_level_id WHERE history.student_id=:student ORDER BY history.id DESC");
        $stmt->execute(['student'=>(int)$student['id']]); return $stmt->fetchAll()?:[];
    }

    public function evidence(string $yuvaId): array
    {
        $student=$this->student($yuvaId);
        $stmt=$this->pdo->prepare("SELECT evidence_guid,evidence_type,source_type,source_id,organization_code,[status],evidence_date,notes,metadata_json,approved_by_email,approved_by_role,created_at,row_version FROM dbo.leadership_evidence WHERE student_id=:student ORDER BY evidence_date DESC,id DESC");
        $stmt->execute(['student'=>(int)$student['id']]);$rows=$stmt->fetchAll()?:[];foreach($rows as &$row){$row['row_version']=normalize_sqlsrv_rowversion_token($row['row_version']??null);}unset($row);return$rows;
    }

    public function reviewEvidence(string $yuvaId,string $evidenceGuid,array $actor,string $decision,string $note=''): void
    {
        $student=$this->student($yuvaId);$role=(string)($actor['role']??'');$email=strtolower(trim((string)($actor['email']??'')));
        if(!in_array($role,['MasterAdmin','OrganizationAdmin'],true)||$email==='')throw new RuntimeException('Authorized human reviewer is required.');
        if(!in_array($decision,['Approved','Rejected'],true))throw new InvalidArgumentException('Unsupported evidence decision.');
        $organization=$role==='OrganizationAdmin'?strtoupper(trim((string)($actor['organization_id']??''))):null;
        if($role==='OrganizationAdmin'){$scope=$this->pdo->prepare("SELECT TOP(1)1 FROM dbo.organization_student_membership_requests WHERE student_id=:student AND organization_code=:organization AND [status]=N'Active'");$scope->execute(['student'=>(int)$student['id'],'organization'=>$organization]);if($scope->fetchColumn()===false)throw new RuntimeException('Active same-organization membership is required.');}
        $stmt=$this->pdo->prepare("UPDATE dbo.leadership_evidence SET [status]=:status,approved_by_email=:email,approved_by_role=:role,organization_code=COALESCE(organization_code,:organization),notes=CASE WHEN :note=N'' THEN notes ELSE :note2 END,updated_at=SYSUTCDATETIME() WHERE student_id=:student AND evidence_guid=:guid AND [status]=N'Pending'");
        $stmt->execute(['status'=>$decision,'email'=>$email,'role'=>$role,'organization'=>$organization,'note'=>$note,'note2'=>$note,'student'=>(int)$student['id'],'guid'=>$evidenceGuid]);if($stmt->rowCount()!==1)throw new RuntimeException('Pending evidence was not found or was already reviewed.');
    }

    public function addHumanEvidence(string $yuvaId,array $actor,array $input): string
    {
        $student=$this->student($yuvaId);$role=(string)($actor['role']??'');$email=strtolower(trim((string)($actor['email']??'')));
        if(!in_array($role,['MasterAdmin','OrganizationAdmin'],true)||$email==='')throw new RuntimeException('Authorized human reviewer is required.');
        $type=(string)($input['evidence_type']??'');if(!in_array($type,['human_review','leadership_service','peer_support','improvement','leadership_goal'],true))throw new InvalidArgumentException('Unsupported human evidence type.');
        $date=trim((string)($input['evidence_date']??''));$notes=trim((string)($input['notes']??''));if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$notes===''||mb_strlen($notes)>2000)throw new InvalidArgumentException('Evidence date and notes are required.');
        $organization=$role==='OrganizationAdmin'?strtoupper(trim((string)($actor['organization_id']??''))):null;
        if($role==='OrganizationAdmin'){$scope=$this->pdo->prepare("SELECT TOP(1)1 FROM dbo.organization_student_membership_requests WHERE student_id=:student AND organization_code=:organization AND [status]=N'Active'");$scope->execute(['student'=>(int)$student['id'],'organization'=>$organization]);if($scope->fetchColumn()===false)throw new RuntimeException('Active same-organization membership is required.');}
        $source=bin2hex(random_bytes(16));$stmt=$this->pdo->prepare("INSERT dbo.leadership_evidence(student_id,evidence_type,source_type,source_id,organization_code,[status],approved_by_email,approved_by_role,evidence_date,notes) VALUES(:student,:type,N'human_observation',:source,:organization,N'Approved',:email,:role,:date,:notes)");$stmt->execute(['student'=>(int)$student['id'],'type'=>$type,'source'=>$source,'organization'=>$organization,'email'=>$email,'role'=>$role,'date'=>$date,'notes'=>$notes]);return $source;
    }

    private function syncSystemEvidence(int $studentId): void
    {
        foreach(['presentation','human_review'] as $type){$verified=$this->pdo->prepare("INSERT dbo.leadership_evidence(student_id,evidence_type,source_type,source_id,organization_code,[status],approved_by_email,approved_by_role,evidence_date,notes) SELECT verification.student_id,:type,N'presentation_verification',CONVERT(NVARCHAR(36),verification.verification_guid),verification.organization_code,N'Approved',verification.reviewer_email,verification.reviewer_role,CONVERT(date,verification.verified_at),COALESCE(verification.verification_note,N'Human-verified completed presentation') FROM dbo.presentation_verifications verification WHERE verification.student_id=:student AND verification.[status]=N'Verified' AND NOT EXISTS(SELECT 1 FROM dbo.leadership_evidence evidence WITH(UPDLOCK,HOLDLOCK) WHERE evidence.student_id=verification.student_id AND evidence.evidence_type=:type2 AND evidence.source_type=N'presentation_verification' AND evidence.source_id=CONVERT(NVARCHAR(36),verification.verification_guid))");$verified->execute(['type'=>$type,'student'=>$studentId,'type2'=>$type]);}
        $ai=$this->pdo->prepare("INSERT dbo.leadership_evidence(student_id,evidence_type,source_type,source_id,[status],evidence_date,notes) SELECT review.student_id,N'applied_ai_review',N'ai_mentor_review',CONVERT(NVARCHAR(120),review.review_key),N'Approved',CONVERT(date,review.applied_at),N'Applied AI Mentor review' FROM dbo.ai_mentor_reviews review WHERE review.student_id=:student AND review.[status]=N'Applied' AND review.applied_at IS NOT NULL AND NOT EXISTS(SELECT 1 FROM dbo.leadership_evidence evidence WITH(UPDLOCK,HOLDLOCK) WHERE evidence.student_id=review.student_id AND evidence.evidence_type=N'applied_ai_review' AND evidence.source_type=N'ai_mentor_review' AND evidence.source_id=CONVERT(NVARCHAR(120),review.review_key))");$ai->execute(['student'=>$studentId]);
    }

    private function evidenceCounts(int $studentId,array $requirements): array
    {
        $stmt=$this->pdo->prepare("SELECT evidence_type,COUNT_BIG(*) amount FROM dbo.leadership_evidence WHERE student_id=:student AND [status]=N'Approved' GROUP BY evidence_type");
        $stmt->execute(['student'=>$studentId]); $counts=[];
        foreach($stmt->fetchAll()?:[] as $row)$counts[(string)$row['evidence_type']]=(int)$row['amount'];
        $presentations=$this->pdo->prepare("SELECT COUNT_BIG(DISTINCT submission_id) FROM dbo.presentation_verifications WHERE student_id=:student AND [status]=N'Verified'");
        $presentations->execute(['student'=>$studentId]);
        $counts['presentations']=max($counts['presentation']??0,(int)$presentations->fetchColumn());
        $reviews=$this->pdo->prepare("SELECT (SELECT COUNT_BIG(*) FROM dbo.ai_mentor_reviews WHERE student_id=:student AND [status]=N'Applied') + (SELECT COUNT_BIG(DISTINCT submission_id) FROM dbo.presentation_verifications WHERE student_id=:student2 AND [status]=N'Verified')");
        $reviews->execute(['student'=>$studentId,'student2'=>$studentId]);
        $counts['reviews']=max(($counts['applied_ai_review']??0)+($counts['human_review']??0),(int)$reviews->fetchColumn());
        $counts['reflections']=$counts['reflection']??0;
        $counts['reflections_or_goal']=($counts['reflection']??0)+($counts['leadership_goal']??0);
        $recentDays=max(1,min(730,(int)($requirements['reflection_recent_days']??180)));
        $recent=$this->pdo->prepare("SELECT SUM(CASE WHEN evidence_type=N'reflection' THEN 1 ELSE 0 END) recent_reflection,SUM(CASE WHEN evidence_type IN(N'reflection',N'leadership_goal') THEN 1 ELSE 0 END) recent_reflection_or_goal FROM dbo.leadership_evidence WHERE student_id=:student AND [status]=N'Approved' AND evidence_date>=DATEADD(DAY,:days,CONVERT(date,SYSUTCDATETIME()))");$recent->bindValue(':student',$studentId,PDO::PARAM_INT);$recent->bindValue(':days',-$recentDays,PDO::PARAM_INT);$recent->execute();$recentRow=$recent->fetch()?:[];$counts['recent_reflection']=(int)($recentRow['recent_reflection']??0);$counts['recent_reflection_or_goal']=(int)($recentRow['recent_reflection_or_goal']??0);
        return $counts;
    }

    public static function calculateRequirements(array $requirements,array $counts): array
    {
        $labels=['presentations'=>'Verified presentations','reviews'=>'Applied AI Mentor or human reviews','reflections'=>'Student reflection','leadership_service'=>'Approved leadership/service contribution','improvement'=>'Human-approved improvement evidence','peer_support'=>'Approved peer-support contribution','recent_reflection'=>'Recent student reflection','recent_reflection_or_goal'=>'Recent reflection or leadership goal'];
        $out=[]; foreach($requirements as $key=>$needed){if(str_ends_with((string)$key,'_days'))continue;$actual=(int)($counts[$key]??0);$out[]=['key'=>$key,'label'=>$labels[$key]??$key,'actual'=>$actual,'required'=>(int)$needed,'complete'=>$actual>=(int)$needed];} return $out;
    }

    private function student(string $yuvaId): array
    {
        $stmt=$this->pdo->prepare("SELECT student.id,student.yuva_id,student.current_level_id,level.name current_level FROM dbo.students student JOIN dbo.levels level ON level.id=student.current_level_id WHERE student.yuva_id=:yuva_id AND student.approval_status=N'approved'");
        $stmt->execute(['yuva_id'=>strtoupper(trim($yuvaId))]); $row=$stmt->fetch(); if(!is_array($row))throw new RuntimeException('Approved student was not found.'); return $row;
    }
    private function rule(int $levelId): ?array { $stmt=$this->pdo->prepare("SELECT TOP(1) rule_version.*, target.name target_level FROM dbo.leadership_rule_versions rule_version JOIN dbo.levels target ON target.id=rule_version.level_to_id WHERE rule_version.level_from_id=:level AND rule_version.rule_version=:version AND rule_version.active=1 ORDER BY rule_version.effective_from DESC,rule_version.id DESC"); $stmt->execute(['level'=>$levelId,'version'=>self::RULE_VERSION]); $row=$stmt->fetch(); return is_array($row)?$row:null; }
    private function terminalProgress(array $student): array { return ['student_id'=>(int)$student['id'],'yuva_id'=>(string)$student['yuva_id'],'current_level'=>(string)$student['current_level'],'current_level_id'=>(int)$student['current_level_id'],'target_level'=>null,'target_level_id'=>null,'rule_version'=>self::RULE_VERSION,'status'=>'Approved','requirements'=>[],'completed'=>0,'required'=>0]; }
    private function transaction(callable $callback): mixed { $this->pdo->beginTransaction(); try{$result=$callback();$this->pdo->commit();return $result;}catch(Throwable $e){db_safe_rollback($this->pdo);throw $e;} }
}

final class LeadershipApprovalService
{
    public function __construct(private readonly PDO $pdo, private readonly LeadershipEligibilityService $eligibility) {}

    public function decide(string $yuvaId,array $actor,array $input): array
    {
        $role=(string)($actor['role']??''); $email=strtolower(trim((string)($actor['email']??'')));
        $isMaster=$role==='MasterAdmin'; $isOrg=$role==='OrganizationAdmin';
        if((!$isMaster&&!$isOrg)||$email===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Human leadership approver is required.');
        $decision=(string)($input['decision']??''); $reason=trim((string)($input['reason']??'')); $override=!empty($input['override']);
        if(!in_array($decision,['Approved','More Evidence Needed'],true)||$reason==='')throw new InvalidArgumentException('A valid decision and reason are required.');
        if($override&&!$isMaster)throw new RuntimeException('Only Master Admin may override eligibility.');
        return $this->transaction(function()use($yuvaId,$actor,$input,$role,$email,$isMaster,$isOrg,$decision,$reason,$override):array{
            $studentStmt=$this->pdo->prepare("SELECT student.id,student.current_level_id,student.yuva_id,current_level.display_order,current_level.name current_level FROM dbo.students student WITH(UPDLOCK,HOLDLOCK) JOIN dbo.levels current_level ON current_level.id=student.current_level_id WHERE student.yuva_id=:yuva_id");
            $studentStmt->execute(['yuva_id'=>strtoupper(trim($yuvaId))]);$student=$studentStmt->fetch();if(!is_array($student))throw new RuntimeException('Student was not found.');
            $snapshotId=(int)($input['snapshot_id']??0);try{$rowVersion=normalize_sqlsrv_rowversion_token($input['row_version']??null);}catch(InvalidArgumentException){throw new RuntimeException('Leadership review version is missing. Refresh before deciding.');}
            if($snapshotId<1)throw new RuntimeException('Leadership review version is missing. Refresh before deciding.');
            $stale=$this->pdo->prepare("SELECT TOP(1) id FROM dbo.leadership_eligibility_snapshots WHERE id=:id AND student_id=:student AND row_version=CONVERT(BINARY(8),:row_version,2)");$stale->bindValue(':id',$snapshotId,PDO::PARAM_INT);$stale->bindValue(':student',(int)$student['id'],PDO::PARAM_INT);$stale->bindValue(':row_version',$rowVersion,PDO::PARAM_STR);$stale->execute();if($stale->fetchColumn()===false)throw new RuntimeException('Stale leadership review was rejected.');
            $organization=null;
            if($isOrg){$organization=strtoupper(trim((string)($actor['organization_id']??'')));if($organization===''||!$this->activeMembership((int)$student['id'],$organization))throw new RuntimeException('Active same-organization membership is required.');}
            $snapshot=$this->eligibility->evaluateByYuvaId($yuvaId,true);
            if(!isset($input['source_revision'])||!hash_equals((string)$snapshot['source_revision'],(string)$input['source_revision']))throw new RuntimeException('Leadership evidence changed. Refresh before deciding.');
            $target=(int)($snapshot['target_level_id']??0); if($target<1)throw new RuntimeException('No next leadership level is available.');
            $targetStmt=$this->pdo->prepare("SELECT id,display_order FROM dbo.levels WHERE id=:id");$targetStmt->execute(['id'=>$target]);$targetRow=$targetStmt->fetch();
            if(!is_array($targetRow)||(int)$targetRow['display_order']!==(int)$student['display_order']+1)throw new RuntimeException('Leadership promotion must advance exactly one level.');
            if($decision==='Approved'&&$snapshot['status']!=='Eligible for Review'&&!($isMaster&&$override))throw new RuntimeException('Student is not currently eligible for approval.');
            $existing=$this->pdo->prepare("SELECT TOP(1) decision_guid FROM dbo.leadership_decisions WHERE student_id=:student AND previous_level_id=:previous AND target_level_id=:target AND decision=N'Approved'");$existing->execute(['student'=>(int)$student['id'],'previous'=>(int)$student['current_level_id'],'target'=>$target]);$prior=$existing->fetchColumn();if($prior!==false)return['status'=>'already-approved','decision_guid'=>(string)$prior];
            $insert=$this->pdo->prepare("INSERT dbo.leadership_decisions(student_id,previous_level_id,target_level_id,eligibility_snapshot_id,decision,decision_reason,approved_by_email,approver_role,organization_code,override_used) OUTPUT INSERTED.id,CONVERT(NVARCHAR(36),INSERTED.decision_guid) AS decision_guid VALUES(:student,:previous,:target,:snapshot,:decision,:reason,:email,:role,:organization,:override)");
            $insert->execute(['student'=>(int)$student['id'],'previous'=>(int)$student['current_level_id'],'target'=>$target,'snapshot'=>(int)$snapshot['snapshot_id'],'decision'=>$decision,'reason'=>$reason,'email'=>$email,'role'=>$role,'organization'=>$organization,'override'=>$override?1:0]);$created=$insert->fetch();
            if($decision==='More Evidence Needed'){$this->pdo->prepare("UPDATE dbo.leadership_eligibility_snapshots SET [status]=N'More Evidence Needed' WHERE id=:id")->execute(['id'=>(int)$snapshot['snapshot_id']]);return['status'=>'more-evidence-needed','decision_guid'=>(string)$created['decision_guid']];}
            $this->pdo->prepare("INSERT dbo.leadership_level_history(student_id,previous_level_id,new_level_id,decision_id,approved_by_email,approver_role,organization_code) VALUES(:student,:previous,:target,:decision,:email,:role,:organization)")->execute(['student'=>(int)$student['id'],'previous'=>(int)$student['current_level_id'],'target'=>$target,'decision'=>(int)$created['id'],'email'=>$email,'role'=>$role,'organization'=>$organization]);
            $promotion=$this->pdo->prepare("UPDATE dbo.students SET current_level_id=:target,updated_at=SYSUTCDATETIME() WHERE id=:student AND current_level_id=:previous");$promotion->execute(['target'=>$target,'student'=>(int)$student['id'],'previous'=>(int)$student['current_level_id']]);if($promotion->rowCount()!==1)throw new RuntimeException('Concurrent leadership promotion was rejected.');
            $this->pdo->prepare("UPDATE dbo.leadership_eligibility_snapshots SET [status]=N'Approved' WHERE id=:id")->execute(['id'=>(int)$snapshot['snapshot_id']]);
            return['status'=>'approved','decision_guid'=>(string)$created['decision_guid']];
        });
    }

    private function activeMembership(int $studentId,string $organization):bool{$stmt=$this->pdo->prepare("SELECT TOP(1)1 FROM dbo.organization_student_membership_requests WHERE student_id=:student AND organization_code=:organization AND [status]=N'Active'");$stmt->execute(['student'=>$studentId,'organization'=>$organization]);return $stmt->fetchColumn()!==false;}
    private function transaction(callable $callback):mixed{$this->pdo->beginTransaction();try{$result=$callback();$this->pdo->commit();return$result;}catch(Throwable $e){db_safe_rollback($this->pdo);throw$e;}}
}

function leadership_eligibility_service(): LeadershipEligibilityService { static $service; return $service??=new LeadershipEligibilityService(Database::connection()); }
function leadership_approval_service(): LeadershipApprovalService { static $service; return $service??=new LeadershipApprovalService(Database::connection(),leadership_eligibility_service()); }
