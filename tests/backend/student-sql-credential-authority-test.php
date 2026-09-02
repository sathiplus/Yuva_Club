<?php
declare(strict_types=1);

function student_credential_assert(bool $condition,string $message):void { if(!$condition) throw new RuntimeException($message); }
$read=static fn(string $path):string=>(string)file_get_contents(__DIR__.'/../../'.$path);
$migration=$read('database/18-student-sql-credential-authority.azure-sql.sql');
$rollback=$read('database/18-student-sql-credential-authority-rollback.sql');
$service=$read('backend/authentication/StudentCredentialService.php');
$registration=$read('submit-registration.php');
$repository=$read('backend/repositories.php');
$forgot=$read('forgot-password.php');
$reset=$read('reset-password.php');
$studentAuth=$read('backend/authentication/StudentAuthentication.php');
$workflow=$read('backend/authentication/StudentLoginWorkflow.php');
$integration=$read('tests/backend/student-sql-credential-authority-integration.php');

foreach(['student_registration_credentials','student_authentication_tokens','token_hash BINARY(32)','used_at','revoked_at','expires_at','ROWVERSION','BEGIN TRANSACTION','COMMIT TRANSACTION'] as $token) student_credential_assert(str_contains($migration,$token),'Migration 18 missing '.$token);
student_credential_assert(!preg_match('/\bpassword\s+NVARCHAR/i',$migration),'Migration must not persist plaintext passwords.');
foreach(['random_bytes(32)',"hash('sha256'",'WITH(UPDLOCK,HOLDLOCK)','password_hash($password','password_changed_at=SYSUTCDATETIME()','credentials_version=credentials_version+1','used_at=SYSUTCDATETIME()','rollBack()'] as $token) student_credential_assert(str_contains($service,$token),'Credential service missing '.$token);
student_credential_assert(str_contains($registration,"'student_password_hash' => db_is_sqlsrv() ? password_hash"),'SQL registration must hash the credential before persistence.');
student_credential_assert(str_contains($registration,'!db_is_sqlsrv()'),'SQL registration must not retain an independent filesystem password authority.');
foreach(['student_registration_credentials WITH (UPDLOCK,HOLDLOCK)','SET password_hash=:password_hash','DELETE FROM dbo.student_registration_credentials'] as $token) student_credential_assert(str_contains($repository,$token),'Approval credential transfer missing '.$token);
student_credential_assert(str_contains($repository,'Student email is already assigned to an established account.'),'Approval must not overwrite an established Student credential.');
student_credential_assert(str_contains($forgot,"student_credential_service()->issueToken"),'SQL reset requests must use SQL tokens.');
student_credential_assert(str_contains($reset,"student_credential_service()->consume"),'SQL reset completion must update SQL credentials.');
student_credential_assert(str_contains($studentAuth,"credentials_version")&&str_contains($workflow,"student_credentials_version"),'Student sessions must enforce credential versions.');
student_credential_assert(str_contains($rollback,'DROP TABLE dbo.student_authentication_tokens')&&str_contains($rollback,'DROP TABLE dbo.student_registration_credentials'),'Migration 18 rollback incomplete.');
foreach(["PHP_SAPI !== 'cli'","YUVA_INTEGRATION_TEST_MODE","strcasecmp(\$database, 'yuva_club')","_rehearsal_","new StudentCredentialService(\$pdo)",'approve_registration(',"revalidateSqlStudentSession",'proc_open(','finally {','m18_cleanup('] as $token) student_credential_assert(str_contains($integration,$token),'Executable SQLSRV harness missing '.$token);
fwrite(STDOUT,"PASS Student SQL credential authority security contracts\n");
