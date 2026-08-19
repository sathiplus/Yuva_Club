<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

interface MediaTranscriptionProvider
{
    /** @return array{ok:bool, transcript?:PresentationTranscript, error_code?:string} */
    public function transcribe(string $path, string $originalName, string $mimeType): array;
    public function providerName(): string;
    public function modelName(): string;
}
