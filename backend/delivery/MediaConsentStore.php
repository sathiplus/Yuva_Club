<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

interface MediaConsentStore
{
    /** @return array{student_granted:bool,parent_required:bool,parent_granted:bool,parent_relationship:?string} */
    public function status(string $yuvaId, string $version): array;
    public function grantStudent(string $yuvaId, string $version): void;
    public function grantParent(string $yuvaId, string $parentEmail, string $version): void;
    public function withdrawParent(string $yuvaId, string $parentEmail, string $version): void;
}
