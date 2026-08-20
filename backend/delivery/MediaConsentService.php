<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class MediaConsentService
{
    public const VERSION = 'media-ai-consent-v1';

    public function __construct(private readonly MediaConsentStore $store) {}

    /** @return array{student_granted:bool,parent_required:bool,parent_granted:bool,parent_relationship:?string,ready:bool,version:string} */
    public function status(string $yuvaId): array
    {
        $status=$this->store->status($yuvaId,self::VERSION);
        $status['ready']=$status['student_granted']&&(!$status['parent_required']||$status['parent_granted']);
        $status['version']=self::VERSION;
        return $status;
    }

    public function acknowledgeStudent(string $yuvaId): array
    {
        $this->store->grantStudent($yuvaId,self::VERSION);
        return $this->status($yuvaId);
    }

    public function grantParent(string $yuvaId,string $parentEmail): array
    {
        $this->store->grantParent($yuvaId,$parentEmail,self::VERSION);
        return $this->status($yuvaId);
    }

    public function withdrawParent(string $yuvaId,string $parentEmail): array
    {
        $this->store->withdrawParent($yuvaId,$parentEmail,self::VERSION);
        return $this->status($yuvaId);
    }
}
