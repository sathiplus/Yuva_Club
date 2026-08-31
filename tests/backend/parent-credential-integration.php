<?php
declare(strict_types=1);
use YuvaClub\Authentication\ParentCredentialService;
use YuvaClub\Authentication\LoginThrottle;
require_once __DIR__.'/sqlsrv-integration-environment.php';
$sqlIntegrationConfig=yuva_configure_sqlsrv_integration_environment();
require_once __DIR__.'/../../backend/database.php';
require_once __DIR__.'/../../backend/authentication/ParentCredentialService.php';
require_once __DIR__.'/../../backend/authentication/LoginThrottle.php';
if(getenv('YUVA_RUN_SQL_INTEGRATION')!=='YES')exit(2);
$pdo=Database::connection();yuva_assert_sqlsrv_integration_identity($pdo,$sqlIntegrationConfig);$suffix=strtolower(bin2hex(random_bytes(8)));$email="parent.security.$suffix@example.test";$userId=0;$parentId=0;
try{
 $user=$pdo->prepare("INSERT INTO dbo.users(email,password_hash,role,display_name,status) OUTPUT INSERTED.id VALUES(:email,NULL,N'parent',N'Synthetic Parent Security',N'active')");$user->execute(['email'=>$email]);$userId=(int)$user->fetchColumn();
 $parent=$pdo->prepare("INSERT INTO dbo.parents(user_id,first_name,last_name,relationship) OUTPUT INSERTED.id VALUES(:user_id,N'Synthetic',N'Parent Security',N'Guardian')");$parent->execute(['user_id'=>$userId]);$parentId=(int)$parent->fetchColumn();
 $service=new ParentCredentialService($pdo);$activation=$service->issueToken($email,'activation');if(!is_string($activation))throw new RuntimeException('Activation token not issued.');
 $secret=explode('.',$activation,2)[1]??'';$check=$pdo->prepare('SELECT DATALENGTH(token_hash),CONVERT(VARCHAR(64),token_hash,2) FROM dbo.parent_authentication_tokens WHERE parent_user_id=:id');$check->execute(['id'=>$userId]);$stored=$check->fetch(PDO::FETCH_NUM);if(!is_array($stored)||(int)$stored[0]!==32||hash_equals((string)$stored[1],$secret))throw new RuntimeException('Token hashing contract failed.');
 if(!$service->consume($activation,'activation','SecureParent12!'))throw new RuntimeException('Activation failed.');if($service->consume($activation,'activation','SecureParent12!'))throw new RuntimeException('Activation replay accepted.');
 $reset=$service->issueToken($email,'password_reset');if(!is_string($reset)||!$service->consume($reset,'password_reset','ReplacementParent12!'))throw new RuntimeException('Password reset failed.');if($service->consume($reset,'password_reset','ReplacementParent12!'))throw new RuntimeException('Reset replay accepted.');
 $expired=$service->issueToken($email,'password_reset');$expiredId=(int)explode('.',(string)$expired,2)[0];$pdo->prepare("UPDATE dbo.parent_authentication_tokens SET expires_at=DATEADD(SECOND,-1,SYSUTCDATETIME()) WHERE id=:id")->execute(['id'=>$expiredId]);if($service->consume((string)$expired,'password_reset','ExpiredParent12!'))throw new RuntimeException('Expired reset accepted.');
 $version=(int)$pdo->query("SELECT credentials_version FROM dbo.users WHERE id=$userId")->fetchColumn();if($version!==3)throw new RuntimeException('Credential version did not rotate exactly twice.');
 $throttle=new LoginThrottle($pdo,3,900,900);for($i=0;$i<3;$i++)$throttle->recordFailure('parent-login',$email,'test-network');if(!$throttle->isBlocked('parent-login',$email,'test-network'))throw new RuntimeException('Durable Parent throttle did not block.');$throttle->clear('parent-login',$email,'test-network');if($throttle->isBlocked('parent-login',$email,'test-network'))throw new RuntimeException('Durable Parent throttle did not clear.');
 fwrite(STDOUT,"PASS Parent SQL activation/reset hashing, expiry, replay, and session invalidation\n");
}finally{
 if($userId>0){$pdo->beginTransaction();try{$pdo->prepare('DELETE FROM dbo.authentication_attempts WHERE account_hash=CONVERT(BINARY(32),:hash,2)')->execute(['hash'=>hash('sha256',$email)]);$pdo->prepare('DELETE FROM dbo.parent_authentication_tokens WHERE parent_user_id=:id')->execute(['id'=>$userId]);$pdo->prepare('DELETE FROM dbo.activity_logs WHERE actor_user_id=:id')->execute(['id'=>$userId]);if($parentId>0)$pdo->prepare('DELETE FROM dbo.parents WHERE id=:id')->execute(['id'=>$parentId]);$pdo->prepare('DELETE FROM dbo.users WHERE id=:id')->execute(['id'=>$userId]);$pdo->commit();}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}}
}
