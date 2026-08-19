<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

use PDO;
use RuntimeException;

final class SqlMediaConsentRepository implements MediaConsentStore
{
    public function __construct(private readonly PDO $pdo) {}

    public function status(string $yuvaId,string $version): array
    {
        $q=$this->pdo->prepare("SELECT TOP 1 s.id,s.date_of_birth,CASE WHEN s.date_of_birth IS NULL OR DATEADD(YEAR,18,s.date_of_birth)>CAST(SYSUTCDATETIME() AS DATE) THEN 1 ELSE 0 END parent_required,(SELECT COUNT(*) FROM dbo.ai_mentor_media_consents c WHERE c.student_id=s.id AND c.consent_version=:student_version AND c.actor_type=N'Student' AND c.status=N'Granted') student_granted,(SELECT COUNT(*) FROM dbo.ai_mentor_media_consents c WHERE c.student_id=s.id AND c.consent_version=:parent_version AND c.actor_type=N'Parent' AND c.status=N'Granted') parent_granted,(SELECT TOP 1 c.parent_relationship FROM dbo.ai_mentor_media_consents c WHERE c.student_id=s.id AND c.consent_version=:relationship_version AND c.actor_type=N'Parent' AND c.status=N'Granted' ORDER BY c.consented_at DESC) parent_relationship FROM dbo.students s WHERE s.yuva_id=:yuva_id");
        $q->execute(['student_version'=>$version,'parent_version'=>$version,'relationship_version'=>$version,'yuva_id'=>$yuvaId]);$row=$q->fetch(PDO::FETCH_ASSOC);
        if(!is_array($row))throw new RuntimeException('Student identity unavailable.');
        return ['student_granted'=>(int)$row['student_granted']>0,'parent_required'=>(int)$row['parent_required']===1,'parent_granted'=>(int)$row['parent_granted']>0,'parent_relationship'=>$row['parent_relationship']!==null?(string)$row['parent_relationship']:null];
    }

    public function grantStudent(string $yuvaId,string $version): void
    {
        $student=$this->pdo->prepare('SELECT id FROM dbo.students WHERE yuva_id=:yuva_id');
        $student->execute(['yuva_id'=>$yuvaId]);
        if($student->fetchColumn()===false)throw new RuntimeException('Student identity unavailable.');
        $q=$this->pdo->prepare("MERGE dbo.ai_mentor_media_consents WITH(HOLDLOCK) target USING(SELECT id FROM dbo.students WHERE yuva_id=:yuva_id) source ON target.student_id=source.id AND target.consent_version=:version AND target.actor_type=N'Student' AND target.parent_id IS NULL WHEN MATCHED AND target.status<>N'Granted' THEN UPDATE SET status=N'Granted',consented_at=SYSUTCDATETIME(),withdrawn_at=NULL,updated_at=SYSUTCDATETIME() WHEN NOT MATCHED THEN INSERT(student_id,yuva_id,consent_version,actor_type,status,consented_at) VALUES(source.id,:yuva_id_insert,:version_insert,N'Student',N'Granted',SYSUTCDATETIME());");
        $q->execute(['yuva_id'=>$yuvaId,'version'=>$version,'yuva_id_insert'=>$yuvaId,'version_insert'=>$version]);
    }

    public function grantParent(string $yuvaId,string $parentEmail,string $version): void
    {
        $link=$this->parentLink($yuvaId,$parentEmail);
        $q=$this->pdo->prepare("MERGE dbo.ai_mentor_media_consents WITH(HOLDLOCK) target USING(SELECT :student_id student_id,:parent_id parent_id) source ON target.student_id=source.student_id AND target.consent_version=:version AND target.actor_type=N'Parent' AND target.parent_id=source.parent_id WHEN MATCHED AND target.status<>N'Granted' THEN UPDATE SET status=N'Granted',parent_relationship=:relationship,consented_at=SYSUTCDATETIME(),withdrawn_at=NULL,updated_at=SYSUTCDATETIME() WHEN NOT MATCHED THEN INSERT(student_id,yuva_id,consent_version,actor_type,parent_id,parent_relationship,status,consented_at) VALUES(:student_id_insert,:yuva_id,:version_insert,N'Parent',:parent_id_insert,:relationship_insert,N'Granted',SYSUTCDATETIME());");
        $q->execute(['student_id'=>$link['student_id'],'parent_id'=>$link['parent_id'],'version'=>$version,'relationship'=>$link['relationship'],'student_id_insert'=>$link['student_id'],'yuva_id'=>$yuvaId,'version_insert'=>$version,'parent_id_insert'=>$link['parent_id'],'relationship_insert'=>$link['relationship']]);
    }

    public function withdrawParent(string $yuvaId,string $parentEmail,string $version): void
    {
        $link=$this->parentLink($yuvaId,$parentEmail);
        $q=$this->pdo->prepare("UPDATE dbo.ai_mentor_media_consents SET status=N'Withdrawn',withdrawn_at=SYSUTCDATETIME(),updated_at=SYSUTCDATETIME() WHERE student_id=:student_id AND parent_id=:parent_id AND consent_version=:version AND actor_type=N'Parent' AND status=N'Granted'");
        $q->execute(['student_id'=>$link['student_id'],'parent_id'=>$link['parent_id'],'version'=>$version]);
    }

    /** @return array{student_id:int,parent_id:int,relationship:string} */
    private function parentLink(string $yuvaId,string $parentEmail): array
    {
        $q=$this->pdo->prepare("SELECT TOP 1 s.id student_id,p.id parent_id,COALESCE(NULLIF(p.relationship,N''),N'Parent/Guardian') relationship FROM dbo.students s JOIN dbo.student_parents sp ON sp.student_id=s.id AND sp.consent_status=N'granted' JOIN dbo.parents p ON p.id=sp.parent_id JOIN dbo.users u ON u.id=p.user_id AND u.status=N'active' WHERE s.yuva_id=:yuva_id AND LOWER(u.email)=LOWER(:email) ORDER BY sp.is_primary DESC,p.id");
        $q->execute(['yuva_id'=>$yuvaId,'email'=>trim($parentEmail)]);$row=$q->fetch(PDO::FETCH_ASSOC);
        if(!is_array($row))throw new RuntimeException('Authorized parent relationship unavailable.');
        return ['student_id'=>(int)$row['student_id'],'parent_id'=>(int)$row['parent_id'],'relationship'=>(string)$row['relationship']];
    }
}
