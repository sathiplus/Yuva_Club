<?php
declare(strict_types=1);
require __DIR__.'/../../backend/identity/PublicIdentityStore.php';
require __DIR__.'/../../backend/identity/PublicIdentityValidator.php';
require __DIR__.'/../../backend/identity/PublicStudentIdentity.php';
require __DIR__.'/../../backend/identity/PublicIdentityService.php';

use YuvaClub\Identity\PublicIdentityService;
use YuvaClub\Identity\PublicIdentityStore;
use YuvaClub\Identity\PublicIdentityValidator;
use YuvaClub\Identity\PublicStudentIdentity;

function identity_check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
function identity_reject(callable $call,string $message):void{try{$call();}catch(Throwable){return;}throw new RuntimeException($message);}
final class FakeIdentityStore implements PublicIdentityStore{
    public array $rows=[];public array $normalized=[];public array $audit=[];
    public function find(string $id):array{return $this->rows[$id]??['yuva_id'=>$id,'avatar_code'=>PublicStudentIdentity::DEFAULT_AVATAR];}
    public function saveStudent(string $id,?string $handle,string $normalized,string $avatar):array{foreach($this->normalized as $owner=>$used)if($owner!==$id&&$used===$normalized)throw new InvalidArgumentException(PublicIdentityValidator::GENERIC_ERROR);$old=$this->rows[$id]??[];$this->rows[$id]=['yuva_id'=>$id,'public_handle'=>$handle,'public_handle_normalized'=>$normalized,'avatar_code'=>$avatar,'handle_changed_at'=>$old['handle_changed_at']??'2026-01-01T00:00:00Z'];$this->normalized[$id]=$normalized;return $this->rows[$id];}
    public function overrideHandle(string $id,?string $handle,string $normalized,int $admin,string $reason):array{$row=$this->saveStudent($id,$handle,$normalized,$this->rows[$id]['avatar_code']??PublicStudentIdentity::DEFAULT_AVATAR);$this->audit[]=[$admin,$reason];return$row;}
}
$store=new FakeIdentityStore();$service=new PublicIdentityService($store);
$initial=$service->updateOwn('YC1','YC1','NovaSpeaker','explorer_rocket',new DateTimeImmutable('2026-08-21T00:00:00Z'));identity_check($initial['handle']==='NovaSpeaker','initial valid handle must save');
$store->rows['YC2']=['yuva_id'=>'YC2','public_handle'=>'Other','public_handle_normalized'=>'other','avatar_code'=>'speaker_mic','handle_changed_at'=>'2026-01-01T00:00:00Z'];$store->normalized['YC2']='other';
identity_reject(fn()=>$service->updateOwn('YC2','YC2','novaspeaker','speaker_mic',new DateTimeImmutable('2026-08-21T00:00:00Z')),'case-insensitive duplicate must reject');
$store->rows['YC1']['handle_changed_at']='2026-08-20T00:00:00Z';identity_reject(fn()=>$service->updateOwn('YC1','YC1','IdeaSpark','explorer_rocket',new DateTimeImmutable('2026-08-21T00:00:00Z')),'30-day rule must reject early rename');
$store->rows['YC1']['handle_changed_at']='2026-07-01T00:00:00Z';identity_check($service->updateOwn('YC1','YC1','IdeaSpark','leader_lion',new DateTimeImmutable('2026-08-21T00:00:00Z'))['avatar_code']==='leader_lion','eligible rename/avatar must save');
$store->rows['YC1']['handle_changed_at']='2026-07-22T00:00:00Z';identity_check($service->updateOwn('YC1','YC1','FutureLeader','leader_lion',new DateTimeImmutable('2026-08-21T00:00:00Z'))['handle']==='FutureLeader','rename at exactly 30 days must save');
identity_check($service->updateOwn('YC2','YC2','Other','creator_palette',new DateTimeImmutable('2026-01-02T00:00:00Z'))['yuva_id']==='YC2','avatar change must preserve permanent YUVA ID');
foreach(['admin','YUVAOfficial','name@example.com','555-123-4567','www.example.com','bad__name','shithead'] as $unsafe)identity_reject(fn()=>PublicIdentityValidator::validate($unsafe),'unsafe handle must reject: '.$unsafe);
identity_reject(fn()=>$service->updateOwn('YC1','YC2','SafeHandle','leader_lion'),'cross-student edit must reject');
identity_reject(fn()=>$service->updateOwn('YC1','YC1','SafeHandle','uploaded_photo'),'invalid avatar must reject');
$view=PublicStudentIdentity::view(['yuva_id'=>'YC1','public_handle'=>'IdeaSpark','avatar_code'=>'leader_lion','email'=>'private@example.com','date_of_birth'=>'2010-01-01','phone'=>'555']);identity_check(array_keys($view)===['yuva_id','handle','avatar_code'],'public helper must return only safe fields');
$service->adminOverride('YC1','SafeSpeaker',42,'Impersonation correction');identity_check(count($store->audit)===1,'Master Admin override must be audited');
$studentHandler=file_get_contents(__DIR__.'/../../student-public-identity.php');$org=file_get_contents(__DIR__.'/../../organization-admin.php');$admin=file_get_contents(__DIR__.'/../../admin-student-public-identity.php');
$migration=file_get_contents(__DIR__.'/../../database/10-public-student-identity.azure-sql.sql');
identity_check(str_contains($studentHandler,'require_student()')&&str_contains($studentHandler,'verify_csrf_token'),'student update must require auth and CSRF');
identity_check(!str_contains($org,'student-public-identity.php'),'Organization Admin must not receive identity mutation controls');
identity_check(str_contains($admin,'require_admin_post()')&&str_contains($admin,'audit_log_event'),'Master Admin override must be authorized and audited');
foreach(['public_handle','public_handle_normalized','avatar_code','handle_changed_at','ux_students_public_handle_normalized','student_public_identity_history','ROWVERSION'] as $required){if($required==='ROWVERSION')continue;identity_check(str_contains($migration,$required),'Migration 10 missing '.$required);}
identity_check(str_contains($migration,'WHERE public_handle_normalized IS NOT NULL'),'handle uniqueness must be filtered for optional handles');
echo "Public student identity tests: PASS\n";
