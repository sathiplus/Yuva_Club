<?php
declare(strict_types=1);
// Real SQL services; fake mail transport only. Refuses any non-disposable/local database.
require_once __DIR__.'/sqlsrv-integration-environment.php';
$config=yuva_configure_sqlsrv_integration_environment();
putenv('APP_URL=https://www.yuvaclub.app');
require_once __DIR__.'/../../backend/database.php';
require_once __DIR__.'/../../backend/subscription-entitlement.php';
if(!defined('YUVA_ROLE_MASTER_ADMIN'))define('YUVA_ROLE_MASTER_ADMIN','MasterAdmin');
if(getenv('YUVA_RUN_SQL_INTEGRATION')!=='YES')throw new RuntimeException('Explicit integration opt-in required.');
$pdo=Database::connection();yuva_assert_sqlsrv_integration_identity($pdo,$config);
$service=new SubscriptionEntitlementService($pdo);
function biCheck(bool $ok,string $label):void {if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label."\n";}
function biRejected(callable $call,string $label):void {$rejected=false;try{$call();}catch(RuntimeException){$rejected=true;}biCheck($rejected,$label);}
function biQuery(PDO $db,string $sql,array $values=[]):PDOStatement {$s=$db->prepare($sql);$s->execute($values);return $s;}
if(($argv[1]??'')==='worker') {
    $actor=['role'=>YUVA_ROLE_MASTER_ADMIN,'email'=>$argv[4]];
    try {
        $service->emailInvitation($actor,$argv[2],$argv[3],static function()use($pdo,$actor):bool {
            biQuery($pdo,"INSERT dbo.subscription_audit(action_type,actor_role,actor_identifier,entity_type,succeeded) VALUES(N'SyntheticProviderCall',N'Master Admin',:actor,N'invitation',1)",['actor'=>$actor['email']]);
            usleep(200000);return true;
        });
        echo "accepted\n";
    } catch(RuntimeException $error) {
        if(!str_starts_with($error->getMessage(),'An invitation already exists.')){fwrite(STDERR,"Unexpected worker failure\n");exit(2);}
        echo "duplicate rejected\n";
    }
    exit;
}
$suffix=strtoupper(bin2hex(random_bytes(5)));
$actor=['role'=>YUVA_ROLE_MASTER_ADMIN,'email'=>'synthetic.invite.'.$suffix.'@example.test'];
$ids=[];$users=[];$campaigns=[];$allCodes=[];$processes=[];$intentionalFailure=false;
$tables=['users','students','promo_campaigns','promo_invitations','promo_campaign_participants','student_entitlements','subscription_audit'];
$before=[];foreach($tables as $table)$before[$table]=(int)$pdo->query('SELECT COUNT(*) FROM dbo.'.$table)->fetchColumn();
$campaign=function(string $label)use($service,$actor,$suffix,&$campaigns):array {
    $row=$service->createCampaign($actor,['campaign_code'=>'CI-'.$suffix.'-'.$label,'display_name'=>'Synthetic invitation test','starts_at'=>gmdate('Y-m-d H:i:s',time()-3600),'entitlement_duration_days'=>30,'max_redemptions'=>2]);
    $campaigns[]=$row;$service->setCampaignStatus($actor,$row['campaign_guid'],'active');return $row;
};
try {
    $program=(int)$pdo->query('SELECT TOP 1 id FROM dbo.programs WHERE is_active=1 ORDER BY id')->fetchColumn();
    foreach(['A','B'] as $label) {
        $email='synthetic.invite.'.$suffix.'.'.$label.'@example.test';
        $user=(int)biQuery($pdo,"INSERT dbo.users(email,role,display_name,status) OUTPUT INSERTED.id VALUES(:email,N'student',N'Synthetic Invitation Student',N'active')",['email'=>$email])->fetchColumn();$users[]=$user;
        $yuva='YCT'.$suffix.$label;
        $id=(int)biQuery($pdo,"INSERT dbo.students(user_id,program_id,yuva_id,first_name,last_name,date_of_birth,approval_status,approved_at) OUTPUT INSERTED.id VALUES(:user,:program,:yuva,N'Synthetic',N'Invitation','2010-01-02',N'approved',SYSUTCDATETIME())",['user'=>$user,'program'=>$program,'yuva'=>$yuva])->fetchColumn();
        $ids[$label]=['id'=>$id,'yuva'=>$yuva,'email'=>$email];
    }
    $main=$campaign('MAIN');$code='';$calls=0;
    $mailer=function(string $to,string $subject,string $text,string $html)use(&$code,&$calls,$ids):bool {
        ++$calls;biCheck($to===$ids['A']['email'],'Authoritative Student recipient');
        biCheck($subject==="You're invited to YUVA Club Beta",'Email subject');
        preg_match('/YUVA-BETA-[A-F0-9]{24}/',$text,$match);$code=$match[0]??'';
        biCheck($code!==''&&str_contains($html,$code),'Readable emailed code');
        biCheck(str_contains($html,'href="https://www.yuvaclub.app/portal.php#app-profile"'),'Canonical secret-free production CTA');return true;
    };
    foreach(['Student','Parent','OrganizationAdmin',''] as $role)biRejected(fn()=>$service->emailInvitation(['role'=>$role],$main['campaign_guid'],$ids['A']['yuva'],$mailer),'Unauthorized issuer rejected');
    $result=$service->emailInvitation($actor,$main['campaign_guid'],$ids['A']['yuva'],$mailer);$allCodes[]=$code;
    biCheck($calls===1&&$result['email_accepted']&&!str_contains(json_encode($result),$code),'Exactly one send and safe result');
    biCheck($service->resolve($ids['A']['yuva'])['plan_code']==='free','No entitlement before redemption');
    $stored=biQuery($pdo,"SELECT CONVERT(varchar(64),token_hash,2) digest FROM dbo.promo_invitations WHERE invitation_guid=:guid",['guid'=>$result['invitation_guid']])->fetchColumn();
    biCheck(strtolower((string)$stored)===hash('sha256',$code),'Only hashed token authority stored');
    $storedRow=biQuery($pdo,'SELECT * FROM dbo.promo_invitations WHERE invitation_guid=:guid',['guid'=>$result['invitation_guid']])->fetch(PDO::FETCH_ASSOC);
    biCheck(!str_contains(serialize($storedRow),$code),'Plaintext code absent from stored invitation');
    biRejected(fn()=>$service->emailInvitation($actor,$main['campaign_guid'],$ids['A']['yuva'],$mailer),'Issuance request replay rejected');
    biCheck($calls===1,'Replay causes zero additional provider calls');
    biRejected(fn()=>$service->redeem($ids['B']['yuva'],$code),'Wrong Student rejected');
    biRejected(fn()=>$service->redeem($ids['A']['yuva'],'invalid'),'Invalid code rejected');
    biCheck($service->redeem($ids['A']['yuva'],$code)['plan_code']==='premium','Valid redemption activates Premium');
    biRejected(fn()=>$service->redeem($ids['A']['yuva'],$code),'Second use rejected');
    biCheck((int)biQuery($pdo,'SELECT COUNT(*) FROM dbo.student_entitlements WHERE student_id=:student',['student'=>$ids['A']['id']])->fetchColumn()===1,'Premium created exactly once');
    foreach(['EXPIRED','REVOKED','DISABLED'] as $kind) {
        $c=$campaign($kind);$issued=$service->issueInvitation($actor,$c['campaign_guid'],$ids['B']['yuva']);$allCodes[]=$issued['invitation_code'];
        if($kind==='EXPIRED')biQuery($pdo,'UPDATE dbo.promo_invitations SET expires_at=DATEADD(second,-1,SYSUTCDATETIME()) WHERE invitation_guid=:guid',['guid'=>$issued['invitation_guid']]);
        if($kind==='REVOKED')$service->revokeInvitation($actor,$issued['invitation_guid']);
        if($kind==='DISABLED')$service->setCampaignStatus($actor,$c['campaign_guid'],'disabled');
        biRejected(fn()=>$service->redeem($ids['B']['yuva'],$issued['invitation_code']),$kind.' code rejected');
    }
    foreach(['FALSE','THROW'] as $kind) {
        $c=$campaign($kind);$failedCode='';$failedCalls=0;
        $fail=function(string $to,string $subject,string $text)use($kind,&$failedCode,&$failedCalls):bool {
            ++$failedCalls;preg_match('/YUVA-BETA-[A-F0-9]{24}/',$text,$match);$failedCode=$match[0];
            if($kind==='THROW')throw new RuntimeException($text);return false;
        };
        $failure=$service->emailInvitation($actor,$c['campaign_guid'],$ids['B']['yuva'],$fail);$allCodes[]=$failedCode;
        biCheck($failure['invitation_created']&&!$failure['email_accepted'],'Mail '.$kind.' failure distinguished');
        biRejected(fn()=>$service->emailInvitation($actor,$c['campaign_guid'],$ids['B']['yuva'],$fail),'Failed send not automatically retried');
        biCheck($failedCalls===1,'Failure transport called once');
        biCheck((int)biQuery($pdo,'SELECT COUNT(*) FROM dbo.promo_invitations WHERE invitation_guid=:guid AND revoked_at IS NULL AND used_at IS NULL',['guid'=>$failure['invitation_guid']])->fetchColumn()===1,'Failure leaves valid hashed invitation');
        $service->revokeInvitation($actor,$failure['invitation_guid']);
        $replacement=$service->emailInvitation($actor,$c['campaign_guid'],$ids['B']['yuva'],static fn()=>true);
        biCheck($replacement['email_accepted'],'Explicit revocation permits reissue');
    }
    $race=$campaign('RACE');
    for($i=0;$i<2;$i++) {
        $proc=proc_open([PHP_BINARY,__FILE__,'worker',$race['campaign_guid'],$ids['B']['yuva'],$actor['email']],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
        if(!is_resource($proc))throw new RuntimeException('Could not start concurrency test');
        fclose($pipes[0]);$processes[]=[$proc,$pipes];
    }
    foreach($processes as [$proc,$pipes]) {
        $output=stream_get_contents($pipes[1]);$errors=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        biCheck(proc_close($proc)===0&&$errors==='','Concurrent request finished safely');
    }$processes=[];
    biCheck((int)biQuery($pdo,'SELECT COUNT(*) FROM dbo.promo_invitations WHERE campaign_id=:id',['id'=>$race['id']])->fetchColumn()===1,'Concurrent issuance one invitation');
    biCheck((int)biQuery($pdo,"SELECT COUNT(*) FROM dbo.subscription_audit WHERE actor_identifier=:actor AND action_type=N'SyntheticProviderCall'",['actor'=>$actor['email']])->fetchColumn()===1,'Concurrent issuance one provider call');
    $audit=json_encode(biQuery($pdo,'SELECT * FROM dbo.subscription_audit WHERE actor_identifier=:actor',['actor'=>$actor['email']])->fetchAll(PDO::FETCH_ASSOC));
    foreach($allCodes as $secret)biCheck(!str_contains($audit,$secret),'No plaintext code in audit');
    biCheck(!str_contains($audit,'https://'),'No mail body/URL in audit');
    if(($argv[1]??'')==='failure')throw new LogicException('Intentional fixture cleanup probe');
} catch(LogicException $error) {
    if($error->getMessage()!=='Intentional fixture cleanup probe')throw $error;
    $intentionalFailure=true;
} finally {
    foreach($processes as [$proc,$pipes]){if(is_resource($proc)){proc_terminate($proc);proc_close($proc);}}
    // Only IDs created by this guarded disposable test; FK order, transactional cleanup.
    $pdo->beginTransaction();
    try {
        biQuery($pdo,'DELETE dbo.subscription_audit WHERE actor_identifier=:actor',['actor'=>$actor['email']]);
        foreach($ids as $student) {
            biQuery($pdo,'DELETE dbo.subscription_audit WHERE actor_identifier=:yuva',['yuva'=>$student['yuva']]);
            foreach(['promo_campaign_participants','student_entitlements'] as $table)biQuery($pdo,'DELETE dbo.'.$table.' WHERE student_id=:id',['id'=>$student['id']]);
        }
        foreach($campaigns as $c){biQuery($pdo,'DELETE dbo.promo_invitations WHERE campaign_id=:id',['id'=>$c['id']]);biQuery($pdo,'DELETE dbo.promo_campaigns WHERE id=:id',['id'=>$c['id']]);}
        foreach($ids as $student)biQuery($pdo,'DELETE dbo.students WHERE id=:id',['id'=>$student['id']]);
        foreach($users as $id)biQuery($pdo,'DELETE dbo.users WHERE id=:id',['id'=>$id]);
        foreach($before as $table=>$count)biCheck((int)$pdo->query('SELECT COUNT(*) FROM dbo.'.$table)->fetchColumn()===$count,'Baseline restored '.$table);
        $pdo->commit();
    }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
}
if($intentionalFailure){echo "PASS cleanup after intentional fixture failure\n";exit(17);}
echo "PASS disposable SQLSRV invitation email lifecycle and automatic cleanup\n";
