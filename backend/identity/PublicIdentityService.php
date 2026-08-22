<?php
declare(strict_types=1);

namespace YuvaClub\Identity;

final class PublicIdentityService
{
    public const CHANGE_DAYS = 30;

    public function __construct(private readonly PublicIdentityStore $store) {}

    public function find(string $yuvaId): array
    {
        return PublicStudentIdentity::view($this->store->find($yuvaId));
    }

    public function updateOwn(string $authenticatedYuvaId, string $targetYuvaId, string $handle, string $avatarCode, ?\DateTimeImmutable $now = null): array
    {
        if (!hash_equals(strtoupper(trim($authenticatedYuvaId)), strtoupper(trim($targetYuvaId)))) {
            throw new \RuntimeException('Access denied.');
        }
        if (!isset(PublicStudentIdentity::AVATARS[$avatarCode])) {
            throw new \InvalidArgumentException('Please choose an available YUVA avatar.');
        }
        $handle = trim($handle);
        $normalized = $handle === '' ? '' : PublicIdentityValidator::validate($handle);
        $current = $this->store->find($targetYuvaId);
        $existing = trim((string) ($current['public_handle'] ?? ''));
        if ($existing !== '' && !hash_equals((string) ($current['public_handle_normalized'] ?? ''), $normalized) && trim((string)($current['handle_changed_at']??''))!=='') {
            $changedAt = new \DateTimeImmutable((string) $current['handle_changed_at'], new \DateTimeZone('UTC'));
            $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            if ($changedAt->modify('+' . self::CHANGE_DAYS . ' days') > $now) {
                throw new \RuntimeException('Your YUVA Handle can be changed once every 30 days.');
            }
        }
        return PublicStudentIdentity::view($this->store->saveStudent($targetYuvaId, $handle !== '' ? $handle : null, $normalized, $avatarCode));
    }

    public function adminOverride(string $yuvaId, string $handle, int $adminUserId, string $reason): array
    {
        if ($adminUserId < 1 || trim($reason) === '') {
            throw new \RuntimeException('A valid Master Admin and moderation reason are required.');
        }
        $handle = trim($handle);
        $normalized = $handle === '' ? '' : PublicIdentityValidator::validate($handle);
        return PublicStudentIdentity::view($this->store->overrideHandle($yuvaId, $handle !== '' ? $handle : null, $normalized, $adminUserId, trim($reason)));
    }
}
