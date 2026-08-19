<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class PresentationTranscript
{
    /** @param array<int,array<string,mixed>> $segments @param array<int,array<string,mixed>> $words */
    public function __construct(
        public readonly string $text,
        public readonly float $durationSeconds,
        public readonly array $segments,
        public readonly array $words,
        public readonly string $language,
        public readonly array $providerMetadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'duration_seconds' => $this->durationSeconds,
            'segments' => $this->segments,
            'words' => $this->words,
            'language' => $this->language,
            'provider_metadata' => $this->providerMetadata,
        ];
    }
}
