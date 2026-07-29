<?php
declare(strict_types=1);

namespace YuvaClub\AI;

interface AiProvider
{
    /**
     * @return array{ok: bool, output?: array<string, mixed>, error?: string}
     */
    public function generateStructuredReview(string $prompt): array;
}
