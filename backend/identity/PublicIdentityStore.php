<?php
declare(strict_types=1);

namespace YuvaClub\Identity;

interface PublicIdentityStore
{
    public function find(string $yuvaId): array;
    public function saveStudent(string $yuvaId, ?string $handle, string $normalizedHandle, string $avatarCode): array;
    public function overrideHandle(string $yuvaId, ?string $handle, string $normalizedHandle, int $adminUserId, string $reason): array;
}
