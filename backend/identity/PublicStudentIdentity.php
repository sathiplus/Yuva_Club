<?php
declare(strict_types=1);

namespace YuvaClub\Identity;

final class PublicStudentIdentity
{
    public const DEFAULT_AVATAR = 'explorer_rocket';
    public const AVATARS = [
        'explorer_rocket' => ['icon' => '🚀', 'label' => 'Explorer'],
        'leader_lion' => ['icon' => '🦁', 'label' => 'Leader'],
        'thinker_brain' => ['icon' => '🧠', 'label' => 'Thinker'],
        'speaker_mic' => ['icon' => '🎤', 'label' => 'Speaker'],
        'spark_bolt' => ['icon' => '⚡', 'label' => 'Spark'],
        'global_citizen' => ['icon' => '🌎', 'label' => 'Global Citizen'],
        'scholar_owl' => ['icon' => '🦉', 'label' => 'Scholar'],
        'courage_tiger' => ['icon' => '🐯', 'label' => 'Courage'],
        'rising_star' => ['icon' => '🌟', 'label' => 'Rising Star'],
        'innovator_robot' => ['icon' => '🤖', 'label' => 'Innovator'],
        'creator_palette' => ['icon' => '🎨', 'label' => 'Creator'],
        'discoverer_telescope' => ['icon' => '🔭', 'label' => 'Discoverer'],
    ];

    public static function avatar(string $code): array
    {
        return self::AVATARS[$code] ?? self::AVATARS[self::DEFAULT_AVATAR];
    }

    public static function view(array $row): array
    {
        $yuvaId = strtoupper(trim((string) ($row['yuva_id'] ?? $row['Yuva Club ID'] ?? '')));
        $handle = trim((string) ($row['public_handle'] ?? ''));
        $avatarCode = (string) ($row['avatar_code'] ?? self::DEFAULT_AVATAR);
        if (!isset(self::AVATARS[$avatarCode])) {
            $avatarCode = self::DEFAULT_AVATAR;
        }
        return ['yuva_id' => $yuvaId, 'handle' => $handle !== '' ? $handle : null, 'avatar_code' => $avatarCode];
    }
}
