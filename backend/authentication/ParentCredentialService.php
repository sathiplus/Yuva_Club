<?php
declare(strict_types=1);

namespace YuvaClub\Authentication;

use PDO;
use RuntimeException;
use Throwable;

final class ParentCredentialService
{
    public function __construct(private PDO $pdo) {}

    public function issueToken(string $email, string $purpose, int $ttlSeconds = 3600): ?string
    {
        $email = strtolower(trim($email));
        $this->assertPurpose($purpose);
        if ($ttlSeconds < 60 || $ttlSeconds > 86400) throw new RuntimeException('Invalid Parent token lifetime.');
        $parent = $this->parentByEmail($email);
        if ($parent === null || ($parent['status'] ?? '') !== 'active') return null;
        if ($purpose === 'password_reset' && !$this->isActivated($parent)) return null;

        $secret = bin2hex(random_bytes(32));
        $hashHex = hash('sha256', $secret);
        $this->pdo->beginTransaction();
        try {
            $revoke = $this->pdo->prepare('UPDATE dbo.parent_authentication_tokens SET revoked_at = SYSUTCDATETIME() WHERE parent_user_id = :user_id AND purpose = :purpose AND used_at IS NULL AND revoked_at IS NULL');
            $revoke->execute(['user_id'=>(int)$parent['user_id'], 'purpose'=>$purpose]);
            $insert = $this->pdo->prepare('INSERT INTO dbo.parent_authentication_tokens (parent_user_id, purpose, token_hash, expires_at) OUTPUT INSERTED.id VALUES (:user_id, :purpose, CONVERT(BINARY(32), :token_hash, 2), DATEADD(SECOND, CONVERT(INT, :ttl), SYSUTCDATETIME()))');
            $insert->bindValue(':user_id', (int)$parent['user_id'], PDO::PARAM_INT);
            $insert->bindValue(':purpose', $purpose, PDO::PARAM_STR);
            $insert->bindValue(':token_hash', $hashHex, PDO::PARAM_STR);
            $insert->bindValue(':ttl', $ttlSeconds, PDO::PARAM_INT);
            $insert->execute();
            $id = $insert->fetchColumn();
            if ($id === false) throw new RuntimeException('Parent token creation failed.');
            $this->audit((int)$parent['user_id'], 'parent.'.$purpose.'.requested', true);
            $this->pdo->commit();
            return (string)$id.'.'.$secret;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }

    /** @return array<string,mixed>|null */
    public function tokenRecord(string $token, string $purpose): ?array
    {
        [$id, $hashHex] = $this->parseToken($token) ?? [null, null];
        if ($id === null) return null;
        $query = $this->pdo->prepare('SELECT token.id, token.parent_user_id, token.purpose, token.expires_at, parent_user.email FROM dbo.parent_authentication_tokens token INNER JOIN dbo.users parent_user ON parent_user.id=token.parent_user_id WHERE token.id=:id AND token.purpose=:purpose AND token.token_hash=CONVERT(BINARY(32), :token_hash, 2) AND token.used_at IS NULL AND token.revoked_at IS NULL AND token.expires_at>SYSUTCDATETIME() AND parent_user.role=N\'parent\' AND parent_user.status=N\'active\'');
        $query->execute(['id'=>$id,'purpose'=>$purpose,'token_hash'=>$hashHex]);
        $row=$query->fetch(PDO::FETCH_ASSOC);
        return is_array($row)?$row:null;
    }

    public function consume(string $token, string $purpose, string $password): bool
    {
        [$id, $hashHex] = $this->parseToken($token) ?? [null, null];
        if ($id === null) return false;
        $this->assertPurpose($purpose);
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($passwordHash) || $passwordHash === '') return false;
        $this->pdo->beginTransaction();
        try {
            $query=$this->pdo->prepare('SELECT token.parent_user_id, parent_user.status FROM dbo.parent_authentication_tokens token WITH (UPDLOCK,HOLDLOCK) INNER JOIN dbo.users parent_user WITH (UPDLOCK,HOLDLOCK) ON parent_user.id=token.parent_user_id WHERE token.id=:id AND token.purpose=:purpose AND token.token_hash=CONVERT(BINARY(32), :token_hash, 2) AND token.used_at IS NULL AND token.revoked_at IS NULL AND token.expires_at>SYSUTCDATETIME() AND parent_user.role=N\'parent\'');
            $query->execute(['id'=>$id,'purpose'=>$purpose,'token_hash'=>$hashHex]);
            $row=$query->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row) || ($row['status']??'')!=='active') { $this->pdo->rollBack(); return false; }
            $userId=(int)$row['parent_user_id'];
            $update=$this->pdo->prepare('UPDATE dbo.users SET password_hash=:password_hash, email_verified_at=COALESCE(email_verified_at,SYSUTCDATETIME()), activated_at=COALESCE(activated_at,SYSUTCDATETIME()), password_changed_at=SYSUTCDATETIME(), credentials_version=credentials_version+1, updated_at=SYSUTCDATETIME() WHERE id=:id AND role=N\'parent\' AND status=N\'active\'');
            $update->execute(['password_hash'=>$passwordHash,'id'=>$userId]);
            if ($update->rowCount()!==1) throw new RuntimeException('Parent credential update failed.');
            $used=$this->pdo->prepare('UPDATE dbo.parent_authentication_tokens SET used_at=SYSUTCDATETIME() WHERE id=:id AND used_at IS NULL');
            $used->execute(['id'=>$id]);
            if ($used->rowCount()!==1) throw new RuntimeException('Parent token consumption failed.');
            $this->audit($userId, 'parent.'.$purpose.'.completed', true);
            $this->pdo->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }

    /** @return array<string,mixed>|null */
    public function parentByUserId(int $userId): ?array
    {
        $statement=$this->pdo->prepare('SELECT parent.id AS parent_id, parent_user.id AS user_id, parent_user.email, parent_user.status, parent_user.role, parent_user.credentials_version FROM dbo.parents parent INNER JOIN dbo.users parent_user ON parent_user.id=parent.user_id WHERE parent_user.id=:id');
        $statement->execute(['id'=>$userId]); $row=$statement->fetch(PDO::FETCH_ASSOC); return is_array($row)?$row:null;
    }

    public function verifyPassword(int $userId, string $password): bool
    {
        $statement=$this->pdo->prepare('SELECT password_hash FROM dbo.users WHERE id=:id AND role=N\'parent\' AND status=N\'active\' AND activated_at IS NOT NULL');
        $statement->execute(['id'=>$userId]); $hash=$statement->fetchColumn();
        return is_string($hash) && $hash!=='' && password_verify($password,$hash);
    }

    /** @return array<string,mixed>|null */
    private function parentByEmail(string $email): ?array
    {
        $statement=$this->pdo->prepare('SELECT TOP(1) parent_user.id AS user_id,parent_user.email,parent_user.status,parent_user.role,parent_user.password_hash,parent_user.email_verified_at,parent_user.activated_at FROM dbo.parents parent INNER JOIN dbo.users parent_user ON parent_user.id=parent.user_id WHERE LOWER(LTRIM(RTRIM(parent_user.email)))=:email AND parent_user.role=N\'parent\'');
        $statement->execute(['email'=>$email]); $row=$statement->fetch(PDO::FETCH_ASSOC); return is_array($row)?$row:null;
    }

    private function isActivated(array $parent): bool { return !empty($parent['password_hash']) && !empty($parent['email_verified_at']) && !empty($parent['activated_at']); }
    private function assertPurpose(string $purpose): void { if (!in_array($purpose,['activation','password_reset'],true)) throw new RuntimeException('Unsupported parent token purpose.'); }
    /** @return array{0:int,1:string}|null */
    private function parseToken(string $token): ?array { $parts=explode('.',trim($token),2); if(count($parts)!==2||preg_match('/^[1-9][0-9]*$/',$parts[0])!==1||preg_match('/^[a-f0-9]{64}$/',$parts[1])!==1)return null; return [(int)$parts[0],hash('sha256',$parts[1])]; }
    private function audit(int $userId,string $action,bool $success): void { $statement=$this->pdo->prepare('INSERT INTO dbo.activity_logs(actor_user_id,actor_role,action,entity_type,entity_id,metadata) VALUES(:user_id,N\'parent\',:action,N\'parent_account\',:entity_id,:metadata)'); $statement->execute(['user_id'=>$userId,'action'=>$action,'entity_id'=>$userId,'metadata'=>json_encode(['success'=>$success],JSON_THROW_ON_ERROR)]); }
}
