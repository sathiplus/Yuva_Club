<?php
declare(strict_types=1);
function parent_security_assert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$root=dirname(__DIR__,2);$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$migration=$read('database/17-parent-password-authentication.azure-sql.sql');$rollback=$read('database/17-parent-password-authentication-rollback.sql');$service=$read('backend/authentication/ParentCredentialService.php');$auth=$read('backend/authentication/ParentAuthentication.php');$workflow=$read('backend/authentication/ParentLoginWorkflow.php');$login=$read('parent-login.php');$certificate=$read('certificate.php');
foreach(['parent_authentication_tokens','authentication_attempts','token_hash BINARY(32)','used_at','revoked_at','expires_at','ROWVERSION','credentials_version','password_changed_at','activated_at'] as $token)parent_security_assert(str_contains($migration,$token),'Migration 17 missing '.$token);
foreach(['COL_LENGTH','OBJECT_ID','sys.indexes','BEGIN TRANSACTION','COMMIT TRANSACTION','sp_executesql'] as $token)parent_security_assert(str_contains($migration,$token),'Migration 17 idempotency/transaction contract missing '.$token);
parent_security_assert(!str_contains(strtolower($migration),'raw_token'),'Migration stores raw token.');
foreach(['random_bytes(32)',"hash('sha256'",'CONVERT(BINARY(32), :token_hash, 2)','WITH (UPDLOCK,HOLDLOCK)','password_hash($password, PASSWORD_DEFAULT)','credentials_version=credentials_version+1','used_at=SYSUTCDATETIME()'] as $token)parent_security_assert(str_contains($service,$token),'Credential service missing '.$token);
parent_security_assert(str_contains($auth,'return $this->authenticateSql($parent, $credential);'),'Parent authentication is not SQL-only.');
parent_security_assert(!str_contains($login,'placeholder="YC2026001"'),'Parent login still accepts Child YUVA ID as a credential.');
parent_security_assert(str_contains($workflow,"sessionRegenerator")&&str_contains($workflow,"parent_credentials_version")&&str_contains($workflow,"parent_authenticated_at"),'Parent session hardening incomplete.');
parent_security_assert(str_contains($certificate,'revalidateSqlChildAccess'),'Certificate Parent-child authorization is not revalidated.');
parent_security_assert(str_contains($rollback,'DROP TABLE dbo.parent_authentication_tokens')&&str_contains($rollback,'DROP TABLE dbo.authentication_attempts'),'Migration 17 rollback incomplete.');
fwrite(STDOUT,"PASS Parent password authentication Migration 17 and security contracts\n");
