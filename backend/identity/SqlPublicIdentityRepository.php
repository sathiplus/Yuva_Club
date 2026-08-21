<?php
declare(strict_types=1);

namespace YuvaClub\Identity;

use PDO;

final class SqlPublicIdentityRepository implements PublicIdentityStore
{
    public function __construct(private readonly PDO $pdo) {}

    public function find(string $yuvaId): array
    {
        $query = $this->pdo->prepare('SELECT yuva_id, public_handle, public_handle_normalized, avatar_code, handle_changed_at FROM dbo.students WHERE yuva_id = :yuva_id');
        $query->execute(['yuva_id' => strtoupper(trim($yuvaId))]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : ['yuva_id' => strtoupper(trim($yuvaId)), 'avatar_code' => PublicStudentIdentity::DEFAULT_AVATAR];
    }

    public function saveStudent(string $yuvaId, ?string $handle, string $normalizedHandle, string $avatarCode): array
    {
        return \Database::transaction(function (PDO $pdo) use ($yuvaId, $handle, $normalizedHandle, $avatarCode): array {
            $select = $pdo->prepare('SELECT id, public_handle, public_handle_normalized, avatar_code FROM dbo.students WITH (UPDLOCK, HOLDLOCK) WHERE yuva_id = :yuva_id');
            $select->execute(['yuva_id' => strtoupper(trim($yuvaId))]);
            $before = $select->fetch(PDO::FETCH_ASSOC);
            if (!is_array($before)) throw new \RuntimeException('Student identity is unavailable.');
            $handleChanged = !hash_equals((string) ($before['public_handle_normalized'] ?? ''), $normalizedHandle);
            try {
                $update = $pdo->prepare('UPDATE dbo.students SET public_handle=:handle, public_handle_normalized=:normalized, avatar_code=:avatar, handle_changed_at=CASE WHEN ISNULL(public_handle_normalized,N\'\')<>:normalized2 THEN SYSUTCDATETIME() ELSE handle_changed_at END WHERE id=:id');
                $update->execute(['handle'=>$handle,'normalized'=>$normalizedHandle !== '' ? $normalizedHandle : null,'avatar'=>$avatarCode,'normalized2'=>$normalizedHandle,'id'=>(int)$before['id']]);
            } catch (\PDOException $error) {
                if ((string)$error->getCode() === '23000') throw new \InvalidArgumentException(PublicIdentityValidator::GENERIC_ERROR.' Try '.implode(', ',PublicIdentityValidator::alternatives((string)$handle)).'.');
                throw $error;
            }
            if ($handleChanged || !hash_equals((string)($before['avatar_code'] ?? ''), $avatarCode)) {
                $history=$pdo->prepare("INSERT dbo.student_public_identity_history(student_id,previous_handle,new_handle,previous_avatar_code,new_avatar_code,change_type,actor_type) VALUES(:student,:old_handle,:new_handle,:old_avatar,:new_avatar,N'StudentUpdate',N'Student')");
                $history->execute(['student'=>(int)$before['id'],'old_handle'=>$before['public_handle']??null,'new_handle'=>$handle,'old_avatar'=>$before['avatar_code']??null,'new_avatar'=>$avatarCode]);
            }
            return $this->find($yuvaId);
        });
    }

    public function overrideHandle(string $yuvaId, ?string $handle, string $normalizedHandle, int $adminUserId, string $reason): array
    {
        return \Database::transaction(function(PDO $pdo) use($yuvaId,$handle,$normalizedHandle,$adminUserId,$reason):array{
            $select=$pdo->prepare('SELECT id,public_handle,avatar_code FROM dbo.students WITH(UPDLOCK,HOLDLOCK) WHERE yuva_id=:yuva_id');$select->execute(['yuva_id'=>strtoupper(trim($yuvaId))]);$before=$select->fetch(PDO::FETCH_ASSOC);if(!is_array($before))throw new \RuntimeException('Student identity is unavailable.');
            try{$update=$pdo->prepare('UPDATE dbo.students SET public_handle=:handle,public_handle_normalized=:normalized,handle_changed_at=SYSUTCDATETIME() WHERE id=:id');$update->execute(['handle'=>$handle,'normalized'=>$normalizedHandle!==''?$normalizedHandle:null,'id'=>(int)$before['id']]);}catch(\PDOException $error){if((string)$error->getCode()==='23000')throw new \InvalidArgumentException(PublicIdentityValidator::GENERIC_ERROR);throw $error;}
            $history=$pdo->prepare("INSERT dbo.student_public_identity_history(student_id,previous_handle,new_handle,previous_avatar_code,new_avatar_code,change_type,actor_type,actor_user_id,reason) VALUES(:student,:old_handle,:new_handle,:avatar,:avatar2,N'ModerationOverride',N'MasterAdmin',:actor,:reason)");$history->execute(['student'=>(int)$before['id'],'old_handle'=>$before['public_handle']??null,'new_handle'=>$handle,'avatar'=>$before['avatar_code']??null,'avatar2'=>$before['avatar_code']??null,'actor'=>$adminUserId,'reason'=>$reason]);
            return $this->find($yuvaId);
        });
    }
}
