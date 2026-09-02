<?php
declare(strict_types=1);

use YuvaClub\Authentication\AuthenticationService;
use YuvaClub\Authentication\ParentAuthentication;
use YuvaClub\Authentication\PortalCompatibilityAdapter;
use YuvaClub\Authentication\PortalRepository;
use YuvaClub\Authentication\StudentAuthentication;
use YuvaClub\Authentication\StudentCredentialService;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../../backend/repositories.php';
require_once __DIR__ . '/../../backend/authentication/PortalRepository.php';
require_once __DIR__ . '/../../backend/authentication/PortalCompatibilityAdapter.php';
require_once __DIR__ . '/../../backend/authentication/StudentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/ParentAuthentication.php';
require_once __DIR__ . '/../../backend/authentication/AuthenticationService.php';
require_once __DIR__ . '/../../backend/authentication/StudentCredentialService.php';

function m18_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function m18_guard(PDO $pdo): string
{
    m18_assert(getenv('YUVA_INTEGRATION_TEST_MODE') === '1', 'Destructive integration-test mode is not enabled.');
    m18_assert(app_environment() === 'test', 'APP_ENV=test is required.');
    m18_assert(db_is_sqlsrv($pdo), 'PDO SQLSRV is required.');
    $database = (string) $pdo->query('SELECT DB_NAME()')->fetchColumn();
    m18_assert(strcasecmp($database, 'yuva_club') !== 0, 'Production database is explicitly forbidden.');
    m18_assert(preg_match('/(?:_rehearsal_|_test_?)/i', $database) === 1, 'Database name is not an approved rehearsal/test name.');
    m18_assert(strcasecmp($database, (string) env_value('DB_DATABASE')) === 0, 'Runtime database identity differs from DB_DATABASE.');
    m18_assert((int) $pdo->query("SELECT CASE WHEN OBJECT_ID(N'dbo.student_registration_credentials',N'U') IS NOT NULL AND OBJECT_ID(N'dbo.student_authentication_tokens',N'U') IS NOT NULL THEN 1 ELSE 0 END")->fetchColumn() === 1, 'Migration 18 is not applied.');
    return $database;
}

/** @return array<string,int> */
function m18_counts(PDO $pdo): array
{
    $tables = ['users','students','parents','student_parents','registrations','student_registration_credentials','student_authentication_tokens','activity_logs'];
    $counts = [];
    foreach ($tables as $table) {
        $counts[$table] = (int) $pdo->query('SELECT COUNT(*) FROM dbo.' . $table)->fetchColumn();
    }
    return $counts;
}

function m18_auth(PDO $pdo): AuthenticationService
{
    $repository = PortalRepository::fromPdo($pdo);
    $students = new StudentAuthentication(
        $repository,
        new PortalCompatibilityAdapter(),
        static fn(string $identifier): ?array => null,
        static fn(array $record, string $password): bool => false
    );
    $parents = new ParentAuthentication(
        $repository,
        static fn(string $identifier): ?array => null,
        static fn(string $email): array => [],
        static fn(array $record, string $password): bool => false
    );
    return new AuthenticationService('sql', $students, $parents);
}

/** @return array{user_id:int,student_id:int,registration_id:int,yuva_id:string,email:string} */
function m18_insert_approved_student(PDO $pdo, string $marker, ?string $passwordHash): array
{
    $email = strtolower($marker) . '@example.test';
    $yuvaId = 'YCTM18' . strtoupper(substr(sha1($marker), 0, 12));
    $programId = $pdo->query("SELECT TOP(1) id FROM dbo.programs WHERE is_active=1 ORDER BY id")->fetchColumn();
    m18_assert($programId !== false, 'No active program exists for the synthetic fixture.');
    $user = $pdo->prepare("INSERT INTO dbo.users(email,password_hash,role,display_name,email_verified_at,activated_at,password_changed_at,status) OUTPUT INSERTED.id VALUES(:email,:password_hash,N'student',:name,SYSUTCDATETIME(),SYSUTCDATETIME(),CASE WHEN :has_password=1 THEN SYSUTCDATETIME() ELSE NULL END,N'active')");
    $user->execute(['email'=>$email,'password_hash'=>$passwordHash,'name'=>$marker,'has_password'=>$passwordHash === null ? 0 : 1]);
    $userId = (int) $user->fetchColumn();
    $student = $pdo->prepare("INSERT INTO dbo.students(user_id,program_id,yuva_id,first_name,last_name,date_of_birth,approval_status,approved_at) OUTPUT INSERTED.id VALUES(:user_id,:program_id,:yuva_id,N'YCTEST',N'Credential','2010-01-02',N'approved',SYSUTCDATETIME())");
    $student->execute(['user_id'=>$userId,'program_id'=>(int)$programId,'yuva_id'=>$yuvaId]);
    $studentId = (int) $student->fetchColumn();
    $registration = $pdo->prepare("INSERT INTO dbo.registrations(student_id,status,student_first_name,student_last_name,date_of_birth,age,program_id,parent_name,parent_email,student_email,code_of_conduct_agreed,recording_agreed,parent_permission_granted,reserved_yuva_id,reviewed_at) OUTPUT INSERTED.id VALUES(:student_id,N'approved',N'YCTEST',N'Credential','2010-01-02',16,:program_id,N'YCTEST Parent',:parent_email,:email,1,0,1,:yuva_id,SYSUTCDATETIME())");
    $registration->execute(['student_id'=>$studentId,'program_id'=>(int)$programId,'parent_email'=>'parent.'.strtolower($marker).'@example.test','email'=>$email,'yuva_id'=>$yuvaId]);
    return ['user_id'=>$userId,'student_id'=>$studentId,'registration_id'=>(int)$registration->fetchColumn(),'yuva_id'=>$yuvaId,'email'=>$email];
}

/** @return array{exit:int,stdout:string,stderr:string} */
function m18_worker(string $operation, array $payload): array
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --worker=' . escapeshellarg($operation);
    $pipes = [];
    $process = proc_open($command, [['pipe','r'],['pipe','w'],['pipe','w']], $pipes, __DIR__, null, ['bypass_shell'=>true]);
    m18_assert(is_resource($process), 'Unable to start SQL concurrency worker.');
    fwrite($pipes[0], json_encode($payload, JSON_THROW_ON_ERROR));
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    return ['process'=>$process,'stdout_pipe'=>$pipes[1],'stderr_pipe'=>$pipes[2]];
}

/** @param array{process:resource,stdout_pipe:resource,stderr_pipe:resource} $worker */
function m18_finish_worker(array $worker): array
{
    $stdout = '';
    $stderr = '';
    do {
        $stdout .= stream_get_contents($worker['stdout_pipe']);
        $stderr .= stream_get_contents($worker['stderr_pipe']);
        $status = proc_get_status($worker['process']);
        if ($status['running']) usleep(20000);
    } while ($status['running']);
    $stdout .= stream_get_contents($worker['stdout_pipe']);
    $stderr .= stream_get_contents($worker['stderr_pipe']);
    fclose($worker['stdout_pipe']);
    fclose($worker['stderr_pipe']);
    $exit = proc_close($worker['process']);
    if ($exit === -1) $exit = (int) $status['exitcode'];
    return ['exit'=>$exit,'stdout'=>trim($stdout),'stderr'=>trim($stderr)];
}

function m18_cleanup(PDO $pdo, string $pattern, array $registrationIds, array $studentIds, array $userIds, ?int $counter): void
{
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($studentIds as $studentId) {
        foreach (['leadership_decisions','leadership_level_history','leadership_eligibility_snapshots','leadership_evidence','student_leadership_reflections','competition_entries','quick_challenge_attempts','student_entitlements'] as $table) {
            $exists = (int) $pdo->query("SELECT CASE WHEN OBJECT_ID(N'dbo.{$table}',N'U') IS NULL THEN 0 ELSE 1 END")->fetchColumn();
            if ($exists === 1) {
                try { $pdo->prepare("DELETE FROM dbo.{$table} WHERE student_id=:id")->execute(['id'=>$studentId]); } catch (Throwable) {}
            }
        }
    }
    foreach ($userIds as $userId) {
        $pdo->prepare('DELETE FROM dbo.student_authentication_tokens WHERE student_user_id=:id')->execute(['id'=>$userId]);
        $pdo->prepare('DELETE FROM dbo.activity_logs WHERE actor_user_id=:id OR (entity_type=N\'student_account\' AND entity_id=:entity_id)')->execute(['id'=>$userId,'entity_id'=>$userId]);
    }
    foreach ($registrationIds as $registrationId) {
        $pdo->prepare('DELETE FROM dbo.activity_logs WHERE entity_type=N\'registration\' AND entity_id=:id')->execute(['id'=>$registrationId]);
        $pdo->prepare('DELETE FROM dbo.student_registration_credentials WHERE registration_id=:id')->execute(['id'=>$registrationId]);
    }
    foreach ($studentIds as $studentId) {
        $pdo->prepare('DELETE FROM dbo.student_parents WHERE student_id=:id')->execute(['id'=>$studentId]);
    }
    foreach ($registrationIds as $registrationId) $pdo->prepare('DELETE FROM dbo.registrations WHERE id=:id')->execute(['id'=>$registrationId]);
    foreach ($studentIds as $studentId) $pdo->prepare('DELETE FROM dbo.students WHERE id=:id')->execute(['id'=>$studentId]);
    $parents = $pdo->prepare('SELECT parent.id,parent.user_id FROM dbo.parents parent INNER JOIN dbo.users parent_user ON parent_user.id=parent.user_id WHERE parent_user.email LIKE :pattern');
    $parents->execute(['pattern'=>$pattern]);
    foreach ($parents->fetchAll(PDO::FETCH_ASSOC) as $parent) {
        $pdo->prepare('DELETE FROM dbo.student_parents WHERE parent_id=:id')->execute(['id'=>$parent['id']]);
        $pdo->prepare('DELETE FROM dbo.parents WHERE id=:id')->execute(['id'=>$parent['id']]);
        $userIds[]=(int)$parent['user_id'];
    }
    foreach (array_unique($userIds) as $userId) $pdo->prepare('DELETE FROM dbo.users WHERE id=:id')->execute(['id'=>$userId]);
    if ($counter !== null) $pdo->prepare('UPDATE dbo.yuva_id_counters SET last_number=:value WHERE [year]=:year')->execute(['value'=>$counter,'year'=>(int)gmdate('Y')]);
}

$pdo = db();
m18_guard($pdo);

$workerArg = $argv[1] ?? '';
if (str_starts_with($workerArg, '--worker=')) {
    $payload = json_decode((string) stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    $operation = substr($workerArg, 9);
    if ($operation === 'consume') {
        $result = (new StudentCredentialService($pdo))->consume((string)$payload['token'], 'password_reset', (string)$payload['password']);
        fwrite(STDOUT, $result ? '1' : '0');
        exit(0);
    }
    if ($operation === 'approve') {
        fwrite(STDOUT, approve_registration((int)$payload['registration_id'], (int)$payload['admin_id']));
        exit(0);
    }
    throw new RuntimeException('Unsupported integration worker.');
}

$suffix = strtolower(bin2hex(random_bytes(5)));
$marker = 'YCTEST-CRED-M18-' . strtoupper($suffix);
$pattern = '%'.$suffix.'%';
$before = m18_counts($pdo);
$counterStatement=$pdo->prepare('SELECT last_number FROM dbo.yuva_id_counters WHERE [year]=:year');
$counterStatement->execute(['year'=>(int)gmdate('Y')]);
$counterValue=$counterStatement->fetchColumn();
$counter=$counterValue === false ? null : (int)$counterValue;
$registrationIds=[]; $studentIds=[]; $userIds=[];

try {
    $adminId = $pdo->query("SELECT TOP(1) id FROM dbo.users WHERE role IN(N'admin',N'master_admin') AND status=N'active' ORDER BY id")->fetchColumn();
    m18_assert($adminId !== false, 'No active administrator exists for supported approval.');
    $initialPassword='M18-Initial-'.bin2hex(random_bytes(8));
    $newRegistration=create_registration_with_reserved_yuva_id([
        'student_first_name'=>'YCTEST','student_last_name'=>'Credential '.$suffix,'date_of_birth'=>'2010-01-02','age'=>16,'grade'=>'10','school'=>'Integration','city_state'=>'Test','parent_name'=>'YCTEST Parent '.$suffix,'relationship'=>'Guardian','parent_email'=>'parent.'.$suffix.'@example.test','student_email'=>'new.'.$suffix.'@example.test','code_of_conduct_agreed'=>1,'parent_permission_granted'=>1,'student_password_hash'=>password_hash($initialPassword,PASSWORD_DEFAULT)
    ]);
    $registrationIds[]=(int)$newRegistration['registration_id'];
    $pending=$pdo->prepare('SELECT password_hash FROM dbo.student_registration_credentials WHERE registration_id=:id');
    $pending->execute(['id'=>$newRegistration['registration_id']]);
    $pendingHash=$pending->fetchColumn();
    m18_assert(is_string($pendingHash)&&password_verify($initialPassword,$pendingHash),'Pending SQL credential was not securely hashed.');
    $w1=m18_worker('approve',['registration_id'=>$newRegistration['registration_id'],'admin_id'=>(int)$adminId]);
    $w2=m18_worker('approve',['registration_id'=>$newRegistration['registration_id'],'admin_id'=>(int)$adminId]);
    $a1=m18_finish_worker($w1); $a2=m18_finish_worker($w2);
    $approvalSuccesses = array_values(array_filter([$a1,$a2], static fn(array $result): bool => $result['exit'] === 0));
    m18_assert(count($approvalSuccesses)>=1,'Both concurrent approval attempts failed.');
    if (count($approvalSuccesses)===2) {
        m18_assert($a1['stdout']===$a2['stdout'],'Concurrent approval returned inconsistent identities.');
    }
    $approved=$pdo->prepare('SELECT registration.student_id,student.user_id,student.yuva_id,student_user.password_hash FROM dbo.registrations registration INNER JOIN dbo.students student ON student.id=registration.student_id INNER JOIN dbo.users student_user ON student_user.id=student.user_id WHERE registration.id=:id');
    $approved->execute(['id'=>$newRegistration['registration_id']]); $approvedRow=$approved->fetch(PDO::FETCH_ASSOC);
    m18_assert(is_array($approvedRow)&&password_verify($initialPassword,(string)$approvedRow['password_hash']),'Approval did not transfer the pending SQL credential.');
    $studentIds[]=(int)$approvedRow['student_id']; $userIds[]=(int)$approvedRow['user_id'];
    m18_assert((int)$pdo->query('SELECT COUNT(*) FROM dbo.student_registration_credentials WHERE registration_id='.(int)$newRegistration['registration_id'])->fetchColumn()===0,'Pending credential remained after approval.');
    $auth=m18_auth($pdo);
    m18_assert($auth->authenticateStudent((string)$approvedRow['yuva_id'],$initialPassword)['authenticated']===true,'Approved Student SQL login failed.');
    $audit=$pdo->prepare("SELECT COUNT(*) FROM dbo.activity_logs WHERE action=N'registration.approved' AND entity_type=N'registration' AND entity_id=:id");
    $audit->execute(['id'=>$newRegistration['registration_id']]); m18_assert((int)$audit->fetchColumn()===1,'Concurrent approval duplicated its audit transition.');

    $nullFixture=m18_insert_approved_student($pdo,$marker.'-NULL',null);
    $registrationIds[]=$nullFixture['registration_id']; $studentIds[]=$nullFixture['student_id']; $userIds[]=$nullFixture['user_id'];
    m18_assert($auth->authenticateStudent($nullFixture['yuva_id'],'filesystem-only-password')['authenticated']===false,'SQL NULL hash fell back to filesystem authority.');
    $credentials=new StudentCredentialService($pdo);
    $activation=$credentials->issueToken($nullFixture['email'],'activation',3600);
    m18_assert(is_string($activation)&&$credentials->tokenRecord($activation,'activation')!==null,'Activation token was not issued through the real service.');
    $recoveryPassword='M18-Recovered-'.bin2hex(random_bytes(8));
    m18_assert($credentials->consume($activation,'activation',$recoveryPassword),'NULL-hash recovery failed.');
    m18_assert(!$credentials->consume($activation,'activation',$recoveryPassword),'Activation token replay succeeded.');
    m18_assert($auth->authenticateStudent($nullFixture['yuva_id'],$recoveryPassword)['authenticated']===true,'Recovered SQL Student could not log in.');

    $activePassword='M18-Active-'.bin2hex(random_bytes(8));
    $activeFixture=m18_insert_approved_student($pdo,$marker.'-ACTIVE',password_hash($activePassword,PASSWORD_DEFAULT));
    $registrationIds[]=$activeFixture['registration_id']; $studentIds[]=$activeFixture['student_id']; $userIds[]=$activeFixture['user_id'];
    $login=$auth->authenticateStudent($activeFixture['yuva_id'],$activePassword);
    m18_assert($login['authenticated']===true,'Initial active Student login failed.');
    $version=(int)$login['credentials_version'];
    $reset=$credentials->issueToken($activeFixture['email'],'password_reset',3600);
    m18_assert(is_string($reset),'Password-reset token was not issued.');
    $newPassword='M18-New-'.bin2hex(random_bytes(8));
    m18_assert($credentials->consume($reset,'password_reset',$newPassword),'Active Student reset failed.');
    m18_assert($auth->authenticateStudent($activeFixture['yuva_id'],$activePassword)['authenticated']===false,'Old password remained valid.');
    $fresh=$auth->authenticateStudent($activeFixture['yuva_id'],$newPassword);
    m18_assert($fresh['authenticated']===true,'New password login failed.');
    m18_assert($auth->revalidateSqlStudentSession($activeFixture['yuva_id'],$activeFixture['user_id'],$version)===null,'Old SQL session survived credential rotation.');
    m18_assert(!$credentials->consume($reset,'password_reset',$newPassword),'Reset-token replay succeeded.');

    $expired=$credentials->issueToken($activeFixture['email'],'password_reset',3600);
    m18_assert(is_string($expired),'Expired-token fixture issue failed.');
    [$expiredId]=explode('.',$expired,2);
    $pdo->prepare("UPDATE dbo.student_authentication_tokens SET created_at=DATEADD(HOUR,-2,SYSUTCDATETIME()),expires_at=DATEADD(HOUR,-1,SYSUTCDATETIME()) WHERE id=:id")->execute(['id'=>(int)$expiredId]);
    m18_assert($credentials->tokenRecord($expired,'password_reset')===null&&!$credentials->consume($expired,'password_reset',$newPassword),'Expired token was accepted.');
    $revoked=$credentials->issueToken($activeFixture['email'],'password_reset',3600);
    $replacement=$credentials->issueToken($activeFixture['email'],'password_reset',3600);
    m18_assert(is_string($revoked)&&is_string($replacement)&&$credentials->tokenRecord($revoked,'password_reset')===null,'Revoked token was accepted.');
    m18_assert($credentials->tokenRecord($replacement,'activation')===null,'Wrong-purpose token was accepted.');

    $concurrent=$credentials->issueToken($activeFixture['email'],'password_reset',3600);
    m18_assert(is_string($concurrent),'Concurrent token fixture issue failed.');
    $concurrentPassword='M18-Concurrent-'.bin2hex(random_bytes(8));
    $c1=m18_worker('consume',['token'=>$concurrent,'password'=>$concurrentPassword]);
    $c2=m18_worker('consume',['token'=>$concurrent,'password'=>$concurrentPassword]);
    $r1=m18_finish_worker($c1); $r2=m18_finish_worker($c2);
    m18_assert($r1['exit']===0&&$r2['exit']===0&&substr_count($r1['stdout'].$r2['stdout'],'1')===1,'Concurrent token use did not produce exactly one winner.');
    m18_assert($auth->authenticateStudent($activeFixture['yuva_id'],$concurrentPassword)['authenticated']===true,'Concurrent winner did not establish the credential.');

    $takeoverPassword='M18-Takeover-'.bin2hex(random_bytes(8));
    $takeover=m18_insert_approved_student($pdo,$marker.'-OWNED',password_hash($takeoverPassword,PASSWORD_DEFAULT));
    $registrationIds[]=$takeover['registration_id']; $studentIds[]=$takeover['student_id']; $userIds[]=$takeover['user_id'];
    $conflict=create_registration_with_reserved_yuva_id(['student_first_name'=>'YCTEST','student_last_name'=>'Takeover','date_of_birth'=>'2010-01-02','age'=>16,'parent_name'=>'YCTEST Parent','parent_email'=>'parent.conflict.'.$suffix.'@example.test','student_email'=>$takeover['email'],'code_of_conduct_agreed'=>1,'parent_permission_granted'=>1,'student_password_hash'=>password_hash('Different-'.bin2hex(random_bytes(8)),PASSWORD_DEFAULT)]);
    $registrationIds[]=(int)$conflict['registration_id'];
    try { approve_registration((int)$conflict['registration_id'],(int)$adminId); throw new RuntimeException('Established account was taken over.'); }
    catch (RuntimeException $error) { m18_assert(str_contains($error->getMessage(),'already assigned'),'Account-ownership mismatch returned the wrong failure.'); }
    m18_assert($auth->authenticateStudent($takeover['yuva_id'],$takeoverPassword)['authenticated']===true,'Takeover attempt altered the established account.');

    $rollbackToken=$credentials->issueToken($activeFixture['email'],'password_reset',3600);
    m18_assert(is_string($rollbackToken),'Rollback token issue failed.');
    [$rollbackId]=explode('.',$rollbackToken,2);
    $beforeRollback=$pdo->prepare('SELECT password_hash,credentials_version FROM dbo.users WHERE id=:id'); $beforeRollback->execute(['id'=>$activeFixture['user_id']]); $rollbackState=$beforeRollback->fetch(PDO::FETCH_ASSOC);
    $trigger='tr_m18_force_rollback_'.$suffix;
    $pdo->exec("CREATE TRIGGER dbo.{$trigger} ON dbo.student_authentication_tokens AFTER UPDATE AS BEGIN IF EXISTS(SELECT 1 FROM inserted WHERE id=".(int)$rollbackId." AND used_at IS NOT NULL) THROW 51018,'Synthetic forced rollback.',1; END");
    try { $credentials->consume($rollbackToken,'password_reset','M18-Rollback-'.bin2hex(random_bytes(8))); throw new RuntimeException('Forced rollback did not fail.'); }
    catch (PDOException $error) { m18_assert(str_contains($error->getMessage(),'Synthetic forced rollback'),'Forced rollback returned the wrong SQL failure.'); }
    finally { $pdo->exec("DROP TRIGGER IF EXISTS dbo.{$trigger}"); }
    $afterRollback=$pdo->prepare('SELECT password_hash,credentials_version FROM dbo.users WHERE id=:id'); $afterRollback->execute(['id'=>$activeFixture['user_id']]); $afterState=$afterRollback->fetch(PDO::FETCH_ASSOC);
    m18_assert($afterState===$rollbackState&&$credentials->tokenRecord($rollbackToken,'password_reset')!==null,'Reset transaction left partial state after forced rollback.');

    fwrite(STDOUT,"PASS real registration credential transfer and concurrent approval idempotency\n");
    fwrite(STDOUT,"PASS NULL-hash recovery through activation service\n");
    fwrite(STDOUT,"PASS active reset, old-password rejection, new login, and session invalidation\n");
    fwrite(STDOUT,"PASS expiry, revocation, wrong-purpose, replay, and concurrent token locking\n");
    fwrite(STDOUT,"PASS account-takeover rejection and forced transactional rollback\n");
    fwrite(STDOUT,"PASS SQL authority with filesystem fallback blocked\n");
} finally {
    m18_cleanup($pdo,$pattern,$registrationIds,$studentIds,$userIds,$counter);
}

$after=m18_counts($pdo);
m18_assert($after===$before,'Baseline table counts did not return exactly to their pre-test values.');
$residue=$pdo->prepare("SELECT (SELECT COUNT(*) FROM dbo.users WHERE email LIKE :user_pattern)+(SELECT COUNT(*) FROM dbo.registrations WHERE student_email LIKE :student_pattern OR parent_email LIKE :parent_pattern) AS residue");
$residue->execute(['user_pattern'=>$pattern,'student_pattern'=>$pattern,'parent_pattern'=>$pattern]);
m18_assert((int)$residue->fetchColumn()===0,'Synthetic credential fixture residue remains.');
fwrite(STDOUT,"PASS guaranteed synthetic cleanup and baseline preservation\n");
fwrite(STDOUT,"RECOVERY Existing approved Student with NULL SQL password uses Forgot/Set Up Password, receives a single-use secure link, establishes the SQL password, and retains the same YUVA ID and history.\n");
