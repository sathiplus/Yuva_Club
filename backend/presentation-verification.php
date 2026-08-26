<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';

final class PresentationVerificationService
{
    public function __construct(private readonly PDO $pdo)
    {
        if (!db_is_sqlsrv($pdo)) throw new RuntimeException('Presentation verification requires Azure SQL.');
    }

    public function submitCompleted(string $yuvaId, array $research): array
    {
        $student=$this->student($yuvaId);
        $fields=[];
        foreach(['research_notes','sources_used','presentation_outline','prepared_questions'] as $field){$fields[$field]=trim((string)($research[$field]??''));if($fields[$field]==='')throw new InvalidArgumentException('Complete the research workspace before submitting a presentation for verification.');}
        $revision=hash('sha256',json_encode($fields,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
        return $this->transaction(function()use($student,$fields,$revision):array{db_acquire_application_lock($this->pdo,'presentation-submission:'.(int)$student['id'].':'.$revision,5000);$stmt=$this->pdo->prepare("SELECT TOP(1) id,[status],completed_at FROM dbo.presentation_submissions WITH(UPDLOCK,HOLDLOCK) WHERE student_id=:student AND source_revision_hash=:revision");
        $stmt->execute(['student'=>(int)$student['id'],'revision'=>$revision]);$existing=$stmt->fetch();if(is_array($existing))return['status'=>'already-submitted','submission_id'=>(int)$existing['id']];
        $insert=$this->pdo->prepare("INSERT dbo.presentation_submissions(student_id,research_notes,sources_used,presentation_outline,prepared_questions,[status],source_revision_hash,completed_at) OUTPUT INSERTED.id VALUES(:student,:notes,:sources,:outline,:questions,N'completed',:revision,SYSUTCDATETIME())");
        $insert->execute(['student'=>(int)$student['id'],'notes'=>$fields['research_notes'],'sources'=>$fields['sources_used'],'outline'=>$fields['presentation_outline'],'questions'=>$fields['prepared_questions'],'revision'=>$revision]);
        return['status'=>'submitted','submission_id'=>(int)$insert->fetchColumn()];});
    }

    public function submissionsForStudent(string $yuvaId): array
    {
        $student=$this->student($yuvaId);$stmt=$this->pdo->prepare("SELECT submission.id,submission.[status],submission.completed_at,submission.created_at,verification.verification_guid,verification.[status] verification_status,verification.verification_note,verification.reviewer_role,verification.organization_code,verification.row_version FROM dbo.presentation_submissions submission LEFT JOIN dbo.presentation_verifications verification ON verification.submission_id=submission.id AND verification.[status]=N'Verified' WHERE submission.student_id=:student AND submission.[status] IN(N'submitted',N'completed',N'reviewed') ORDER BY submission.id DESC");$stmt->execute(['student'=>(int)$student['id']]);$rows=$stmt->fetchAll()?:[];foreach($rows as &$row){if(($row['row_version']??null)!==null)$row['row_version']=normalize_sqlsrv_rowversion_token($row['row_version']);}unset($row);return$rows;
    }

    public function verify(string $yuvaId,int $submissionId,array $actor,string $note=''): array
    {
        $student=$this->student($yuvaId);$scope=$this->authorize($actor,(int)$student['id']);$note=trim($note);if(mb_strlen($note)>2000)throw new InvalidArgumentException('Verification note is too long.');
        return $this->transaction(function()use($student,$submissionId,$actor,$scope,$note):array{
            db_acquire_application_lock($this->pdo,'presentation-verification:'.$submissionId,5000);
            $submission=$this->pdo->prepare("SELECT id FROM dbo.presentation_submissions WITH(UPDLOCK,HOLDLOCK) WHERE id=:id AND student_id=:student AND [status] IN(N'submitted',N'completed',N'reviewed')");$submission->execute(['id'=>$submissionId,'student'=>(int)$student['id']]);if($submission->fetchColumn()===false)throw new RuntimeException('Completed presentation submission was not found.');
            $existing=$this->pdo->prepare("SELECT TOP(1) verification_guid FROM dbo.presentation_verifications WHERE submission_id=:submission AND [status]=N'Verified'");$existing->execute(['submission'=>$submissionId]);$prior=$existing->fetchColumn();if($prior!==false)return['status'=>'already-verified','verification_guid'=>(string)$prior];
            $insert=$this->pdo->prepare("INSERT dbo.presentation_verifications(submission_id,student_id,reviewer_user_id,reviewer_email,reviewer_role,organization_code,verification_note) OUTPUT INSERTED.id,CONVERT(NVARCHAR(36),INSERTED.verification_guid) VALUES(:submission,:student,:reviewer,:email,:role,:organization,:note)");
            $insert->execute(['submission'=>$submissionId,'student'=>(int)$student['id'],'reviewer'=>$scope['user_id'],'email'=>$scope['email'],'role'=>$scope['role'],'organization'=>$scope['organization'],'note'=>$note!==''?$note:null]);$created=$insert->fetch();if(!is_array($created))throw new RuntimeException('Verification was not created.');
            foreach(['presentation','human_review'] as $type){$e=$this->pdo->prepare("INSERT dbo.leadership_evidence(student_id,evidence_type,source_type,source_id,organization_code,[status],approved_by_email,approved_by_role,evidence_date,notes) VALUES(:student,:type,N'presentation_verification',:source,:organization,N'Approved',:email,:role,CONVERT(date,SYSUTCDATETIME()),:notes)");$e->execute(['student'=>(int)$student['id'],'type'=>$type,'source'=>(string)$created['verification_guid'],'organization'=>$scope['organization'],'email'=>$scope['email'],'role'=>$scope['role'],'notes'=>$note!==''?$note:'Human-verified completed presentation']);}
            $this->audit((int)$created['id'],'Verified',$scope,$note);
            return['status'=>'verified','verification_guid'=>(string)$created['verification_guid']];
        });
    }

    public function revoke(string $verificationGuid,string $rowVersion,array $actor,string $reason): void
    {
        $scope=$this->authorizeMaster($actor);$reason=trim($reason);if($reason===''||mb_strlen($reason)>2000)throw new InvalidArgumentException('A valid revocation reason and current version are required.');$rowVersion=normalize_sqlsrv_rowversion_token($rowVersion);
        $this->transaction(function()use($verificationGuid,$rowVersion,$scope,$reason):void{$stmt=$this->pdo->prepare("UPDATE dbo.presentation_verifications SET [status]=N'Revoked',revoked_at=SYSUTCDATETIME(),revocation_note=:reason,updated_at=SYSUTCDATETIME() OUTPUT INSERTED.id WHERE verification_guid=:guid AND [status]=N'Verified' AND row_version=CONVERT(BINARY(8),:version,2)");$stmt->bindValue(':reason',$reason,PDO::PARAM_STR);$stmt->bindValue(':guid',$verificationGuid,PDO::PARAM_STR);$stmt->bindValue(':version',$rowVersion,PDO::PARAM_STR);$stmt->execute();$id=$stmt->fetchColumn();if($id===false)throw new RuntimeException('Stale or unavailable verification was rejected.');$this->pdo->prepare("UPDATE dbo.leadership_evidence SET [status]=N'Rejected',updated_at=SYSUTCDATETIME(),notes=CONCAT(notes,N' | Revoked: ',:reason) WHERE source_type=N'presentation_verification' AND source_id=:source AND [status]=N'Approved'")->execute(['reason'=>$reason,'source'=>$verificationGuid]);$this->audit((int)$id,'Revoked',$scope,$reason);});
    }

    private function authorize(array $actor,int $studentId):array{$role=(string)($actor['role']??'');$email=strtolower(trim((string)($actor['email']??'')));if(!in_array($role,['MasterAdmin','OrganizationAdmin'],true)||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Authorized human reviewer is required.');$organization=null;if($role==='OrganizationAdmin'){$organization=strtoupper(trim((string)($actor['organization_id']??'')));$stmt=$this->pdo->prepare("SELECT TOP(1)1 FROM dbo.organization_student_membership_requests WHERE student_id=:student AND organization_code=:organization AND [status]=N'Active'");$stmt->execute(['student'=>$studentId,'organization'=>$organization]);if($organization===''||$stmt->fetchColumn()===false)throw new RuntimeException('Active same-organization membership is required.');}$user=$this->pdo->prepare("SELECT TOP(1) id FROM dbo.users WHERE LOWER(email)=:email AND role=N'admin' AND [status]=N'active'");$user->execute(['email'=>$email]);$id=$user->fetchColumn();if($role==='MasterAdmin'&&$id===false)throw new RuntimeException('Authorized human reviewer is required.');return['role'=>$role,'email'=>$email,'organization'=>$organization,'user_id'=>$id!==false?(int)$id:null];}
    private function authorizeMaster(array $actor):array{$scope=$this->authorize($actor,0);if($scope['role']!=='MasterAdmin')throw new RuntimeException('Only Master Admin may revoke a presentation verification.');return$scope;}
    private function student(string $yuvaId):array{$stmt=$this->pdo->prepare("SELECT id,yuva_id FROM dbo.students WHERE yuva_id=:yuva_id AND approval_status=N'approved'");$stmt->execute(['yuva_id'=>strtoupper(trim($yuvaId))]);$row=$stmt->fetch();if(!is_array($row))throw new RuntimeException('Approved student was not found.');return$row;}
    private function audit(int $id,string $action,array $scope,string $reason):void{$stmt=$this->pdo->prepare("INSERT dbo.presentation_verification_audit(verification_id,action_type,actor_email,actor_role,organization_code,reason) VALUES(:id,:action,:email,:role,:organization,:reason)");$stmt->execute(['id'=>$id,'action'=>$action,'email'=>$scope['email'],'role'=>$scope['role'],'organization'=>$scope['organization'],'reason'=>$reason!==''?$reason:null]);}
    private function transaction(callable $callback):mixed{$this->pdo->beginTransaction();try{$result=$callback();$this->pdo->commit();return$result;}catch(Throwable $e){db_safe_rollback($this->pdo);throw$e;}}
}

function presentation_verification_service(): PresentationVerificationService { static $service; return $service??=new PresentationVerificationService(Database::connection()); }
