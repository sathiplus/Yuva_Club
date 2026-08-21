<?php
declare(strict_types=1);

namespace YuvaClub\Identity;

final class PublicIdentityValidator
{
    public const GENERIC_ERROR = 'This YUVA Handle is not available. Please choose another.';
    private const RESERVED = ['admin','administrator','masteradmin','yuvaclub','yuva','support','official','moderator','system','staff','helpdesk'];
    private const BLOCKED = ['fuck','shit','bitch','asshole','nigger','nigga','cunt','porn','sex'];

    public static function normalize(string $handle): string
    {
        return strtolower(trim($handle));
    }

    public static function validate(string $handle): string
    {
        $handle = trim($handle);
        $normalized = self::normalize($handle);
        if (strlen($handle) < 3 || strlen($handle) > 24
            || preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?$/', $handle) !== 1
            || preg_match('/[._-]{2,}/', $handle) === 1
            || filter_var($handle, FILTER_VALIDATE_EMAIL) !== false
            || preg_match('/(?:https?|www\.|\.com|\.net|\.org|\.app|\.io)/i', $handle) === 1
            || preg_match('/(?:\+?\d[\s().-]*){7,}/', $handle) === 1
            || preg_match('/\d{1,5}[._-]?(?:street|st|road|rd|avenue|ave|lane|ln|drive|dr)\b/i', $handle) === 1
        ) {
            throw new \InvalidArgumentException(self::GENERIC_ERROR);
        }
        $collapsed = preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
        foreach (self::RESERVED as $reserved) {
            if ($collapsed === $reserved || str_contains($collapsed, $reserved)) {
                throw new \InvalidArgumentException(self::GENERIC_ERROR);
            }
        }
        foreach (self::BLOCKED as $blocked) {
            if (str_contains($collapsed, $blocked)) {
                throw new \InvalidArgumentException(self::GENERIC_ERROR);
            }
        }
        return $normalized;
    }

    public static function alternatives(string $handle): array
    {
        $base = preg_replace('/[^A-Za-z0-9]/', '', trim($handle)) ?: 'YuvaStar';
        $base = substr($base, 0, 18);
        return [$base . '7', $base . 'Star', $base . 'Spark'];
    }
}
