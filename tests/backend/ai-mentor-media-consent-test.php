<?php
declare(strict_types=1);
require __DIR__.'/../../backend/delivery/MediaConsentStore.php';
require __DIR__.'/../../backend/delivery/MediaConsentService.php';
require __DIR__.'/../../backend/delivery/PresentationMediaManager.php';

use YuvaClub\Delivery\MediaConsentService;
use YuvaClub\Delivery\MediaConsentStore;
use YuvaClub\Delivery\PresentationMediaManager;

function check(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
final class FakeConsentStore implements MediaConsentStore{
    public bool $student=false;public bool $parent=false;public bool $required=true;public int $studentWrites=0;
    public function status(string $id,string $version):array{return['student_granted'=>$this->student,'parent_required'=>$this->required,'parent_granted'=>$this->parent,'parent_relationship'=>$this->parent?'Guardian':null];}
    public function grantStudent(string $id,string $version):void{if(!$this->student)$this->studentWrites++;$this->student=true;}
    public function grantParent(string $id,string $email,string $version):void{$this->parent=true;}
    public function withdrawParent(string $id,string $email,string $version):void{$this->parent=false;}
}
$store=new FakeConsentStore();$service=new MediaConsentService($store);
check(!$service->status('YC1')['ready'],'minor must be blocked without consent');
check(!$service->acknowledgeStudent('YC1')['ready'],'student consent alone must not authorize minor media');
$service->acknowledgeStudent('YC1');check($store->studentWrites===1,'same consent version must be idempotent');
check($service->grantParent('YC1','parent@example.test')['ready'],'current student and parent consent must authorize media');
check(!$service->withdrawParent('YC1','parent@example.test')['ready'],'parent withdrawal must block media');
$store2=new FakeConsentStore();$store2->required=false;$adult=new MediaConsentService($store2);check($adult->acknowledgeStudent('YC2')['ready'],'adult student acknowledgment must authorize media');

$root=sys_get_temp_dir().DIRECTORY_SEPARATOR.'yuva-media-'.bin2hex(random_bytes(5));
mkdir($root.DIRECTORY_SEPARATOR.'YC1'.DIRECTORY_SEPARATOR.'media',0700,true);mkdir($root.DIRECTORY_SEPARATOR.'YC2'.DIRECTORY_SEPARATOR.'media',0700,true);
file_put_contents($root.DIRECTORY_SEPARATOR.'YC1'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'one.mp3','one');file_put_contents($root.DIRECTORY_SEPARATOR.'YC2'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'two.mp3','two');
$manager=new PresentationMediaManager($root);check($manager->delete('YC1',['stored_filename'=>'one.mp3']),'student media should delete');check(file_exists($root.DIRECTORY_SEPARATOR.'YC2'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'two.mp3'),'unrelated media must remain');check(!$manager->delete('YC2',['stored_filename'=>'../two.mp3']),'traversal must be rejected');
unlink($root.DIRECTORY_SEPARATOR.'YC2'.DIRECTORY_SEPARATOR.'media'.DIRECTORY_SEPARATOR.'two.mp3');rmdir($root.DIRECTORY_SEPARATOR.'YC1'.DIRECTORY_SEPARATOR.'media');rmdir($root.DIRECTORY_SEPARATOR.'YC1');rmdir($root.DIRECTORY_SEPARATOR.'YC2'.DIRECTORY_SEPARATOR.'media');rmdir($root.DIRECTORY_SEPARATOR.'YC2');rmdir($root);

$upload=file_get_contents(__DIR__.'/../../portal-submit-media.php');$admin=file_get_contents(__DIR__.'/../../admin-delivery-review.php');$migration=file_get_contents(__DIR__.'/../../database/09-ai-mentor-phase-1c-media-consent.azure-sql.sql');
check(strpos($upload,'media_consent_service()->acknowledgeStudent')<strpos($upload,'$_FILES'),'consent must be checked before file processing');
check(str_contains($admin,"media_consent_service()->status"),'admin processing must recheck current consent');
check(str_contains($migration,'consent_version')&&str_contains($migration,'row_version'),'consent migration must be versioned and concurrent');
check(!str_contains($upload,'OPENAI_API_KEY')&&!str_contains($admin,'transcript='),'controllers must not log secrets or media contents');
echo "AI Mentor media consent tests: PASS\n";
