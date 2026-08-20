<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

use PDO;
use RuntimeException;

final class SqlDeliveryReviewRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findLatest(string $yuvaId, bool $appliedOnly=false): array
    {
        $sql='SELECT TOP 1 * FROM dbo.ai_mentor_delivery_reviews WHERE yuva_id=:yuva_id'.($appliedOnly?' AND status=N\'Applied\'':'').' ORDER BY id DESC';
        $q=$this->pdo->prepare($sql);$q->execute(['yuva_id'=>$yuvaId]);$row=$q->fetch(PDO::FETCH_ASSOC);return is_array($row)?$this->hydrate($row):[];
    }

    public function createProcessing(string $yuvaId, PresentationMedia $media): int
    {
        $student=$this->pdo->prepare('SELECT id FROM dbo.students WHERE yuva_id=:yuva_id');$student->execute(['yuva_id'=>$yuvaId]);$studentId=$student->fetchColumn();
        if($studentId===false) throw new RuntimeException('Student identity unavailable.');
        $q=$this->pdo->prepare("INSERT dbo.ai_mentor_delivery_reviews(student_id,yuva_id,media_reference,original_filename,media_mime_type,media_size_bytes,media_sha256,source_revision_hash,status) OUTPUT INSERTED.id VALUES(:student_id,:yuva_id,:reference,:filename,:mime,:size,:sha,:source_hash,N'Processing')");
        $q->execute(['student_id'=>(int)$studentId,'yuva_id'=>$yuvaId,'reference'=>$media->reference,'filename'=>$media->originalName,'mime'=>$media->mimeType,'size'=>$media->sizeBytes,'sha'=>$media->sha256,'source_hash'=>str_repeat('0',64)]);return (int)$q->fetchColumn();
    }

    public function complete(int $id, array $result): void
    {
        $ok=($result['ok']??false)===true;
        $q=$this->pdo->prepare("UPDATE dbo.ai_mentor_delivery_reviews SET status=:status,media_duration_seconds=:duration,source_revision_hash=:source_hash,transcription_provider=:provider,transcription_model=:model,coaching_provider=:coaching_provider,coaching_model=:coaching_model,prompt_version=:prompt_version,transcript=:transcript,transcript_timing_json=:timing,deterministic_metrics_json=:metrics,visual_analysis_status=:visual_status,visual_analysis_json=:visual,generated_coaching_result=:generated,error_code=:error,generated_at=CASE WHEN :draft=1 THEN SYSUTCDATETIME() ELSE NULL END,updated_at=SYSUTCDATETIME() WHERE id=:id AND status=N'Processing'");
        $transcript=$result['transcript']??[];
        $q->execute(['id'=>$id,'status'=>$ok?'Draft':'Failed','draft'=>$ok?1:0,'duration'=>$result['duration_seconds']??null,'source_hash'=>$result['source_revision_hash']??str_repeat('0',64),'provider'=>$result['transcription_provider']??null,'model'=>$result['transcription_model']??null,'coaching_provider'=>$result['coaching_provider']??null,'coaching_model'=>$result['coaching_model']??null,'prompt_version'=>$result['prompt_version']??null,'transcript'=>$transcript['text']??null,'timing'=>$transcript===[]?null:json_encode(['segments'=>$transcript['segments']??[],'words'=>$transcript['words']??[],'language'=>$transcript['language']??''],JSON_UNESCAPED_SLASHES),'metrics'=>$ok?json_encode($result['metrics'],JSON_UNESCAPED_SLASHES):null,'visual_status'=>$result['visual_status']??'Unavailable','visual'=>isset($result['visual_analysis'])?json_encode($result['visual_analysis'],JSON_UNESCAPED_SLASHES):null,'generated'=>$ok?json_encode($result['review'],JSON_UNESCAPED_SLASHES):null,'error'=>$ok?null:($result['error_code']??'processing_failed')]);
        if($q->rowCount()!==1) throw new RuntimeException('Delivery review processing state changed.');
    }

    public function saveAdminEdit(string $yuvaId,array $review,string $rowVersion,?int $adminId): bool
    {
        $q=$this->pdo->prepare("UPDATE dbo.ai_mentor_delivery_reviews SET admin_edited_result=:result,reviewed_by=:reviewed,reviewed_at=SYSUTCDATETIME(),updated_at=SYSUTCDATETIME() WHERE id=(SELECT TOP 1 id FROM dbo.ai_mentor_delivery_reviews WHERE yuva_id=:yuva_id ORDER BY id DESC) AND status=N'Draft' AND row_version=CONVERT(BINARY(8),:version,2)");
        $q->execute(['result'=>json_encode($review,JSON_UNESCAPED_SLASHES),'reviewed'=>$adminId,'yuva_id'=>$yuvaId,'version'=>$rowVersion]);return $q->rowCount()===1;
    }

    public function apply(string $yuvaId,string $currentSourceHash,?int $adminId): string
    {
        return \Database::transaction(function(PDO $pdo)use($yuvaId,$currentSourceHash,$adminId):string{
            \db_acquire_application_lock($pdo,'ai-mentor-delivery-apply:'.$yuvaId,0);
            $q=$pdo->prepare("SELECT TOP 1 * FROM dbo.ai_mentor_delivery_reviews WITH(UPDLOCK,HOLDLOCK) WHERE yuva_id=:yuva_id ORDER BY id DESC");$q->execute(['yuva_id'=>$yuvaId]);$row=$q->fetch(PDO::FETCH_ASSOC);
            if(!is_array($row))return'missing';if($row['status']==='Applied')return'already-applied';if($row['status']!=='Draft')return $row['status']==='Stale'?'stale':'missing';
            if(!hash_equals((string)$row['source_revision_hash'],$currentSourceHash)){$pdo->prepare("UPDATE dbo.ai_mentor_delivery_reviews SET status=N'Stale',error_code=N'source_changed',updated_at=SYSUTCDATETIME() WHERE id=:id")->execute(['id'=>$row['id']]);return'stale';}
            $review=json_decode((string)($row['admin_edited_result']?:$row['generated_coaching_result']),true);if(!is_array($review))return'missing';$tokens=max(0,min(4,(int)($review['suggested_tokens']??0)));
            $ledger=$pdo->prepare("INSERT dbo.student_points(student_id,points,tokens,reason,source_type,source_id,awarded_by) SELECT :student,0,:tokens,N'Applied AI Mentor delivery review',N'ai_mentor_delivery_review',:source,:admin WHERE NOT EXISTS(SELECT 1 FROM dbo.student_points WITH(UPDLOCK,HOLDLOCK) WHERE student_id=:student2 AND source_type=N'ai_mentor_delivery_review' AND source_id=:source2)");
            $ledger->execute(['student'=>$row['student_id'],'tokens'=>$tokens,'source'=>$row['id'],'admin'=>$adminId,'student2'=>$row['student_id'],'source2'=>$row['id']]);
            $apply=$pdo->prepare("UPDATE dbo.ai_mentor_delivery_reviews SET status=N'Applied',reviewed_by=COALESCE(reviewed_by,:admin),reviewed_at=COALESCE(reviewed_at,SYSUTCDATETIME()),applied_at=SYSUTCDATETIME(),apply_reference=COALESCE(apply_reference,NEWID()),updated_at=SYSUTCDATETIME() WHERE id=:id AND status=N'Draft'");$apply->execute(['admin'=>$adminId,'id'=>$row['id']]);if($apply->rowCount()!==1)throw new RuntimeException('Delivery review apply concurrency failure.');return'applied';
        },'SERIALIZABLE',true);
    }

    public function markUnappliedStale(string $yuvaId,string $reason): int
    {
        $q=$this->pdo->prepare("UPDATE dbo.ai_mentor_delivery_reviews SET status=N'Stale',error_code=:reason,updated_at=SYSUTCDATETIME() WHERE yuva_id=:yuva_id AND status IN(N'Processing',N'Draft')");
        $q->execute(['reason'=>$reason,'yuva_id'=>$yuvaId]);
        return $q->rowCount();
    }

    private function hydrate(array $row): array
    {
        $generated=json_decode((string)($row['generated_coaching_result']??''),true);$edited=json_decode((string)($row['admin_edited_result']??''),true);
        return $row+['review'=>is_array($edited)?$edited:(is_array($generated)?$generated:[]),'generated_review'=>is_array($generated)?$generated:[],'admin_edited_review'=>is_array($edited)?$edited:[],'version'=>bin2hex((string)$row['row_version'])];
    }
}
