<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class PresentationMedia
{
    public function __construct(
        public readonly string $path,
        public readonly string $reference,
        public readonly string $originalName,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $sha256
    ) {}

    public function sourceRevisionHash(float $duration, string $transcriptionModel): string
    {
        return hash('sha256', json_encode([
            'media_sha256' => $this->sha256,
            'mime' => strtolower($this->mimeType),
            'duration' => round($duration, 3),
            'transcription_model' => $transcriptionModel,
        ], JSON_UNESCAPED_SLASHES) ?: '');
    }
}
