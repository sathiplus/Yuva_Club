<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class MediaUploadValidator
{
    public const MAX_BYTES = 25 * 1024 * 1024;
    public const MAX_DURATION_SECONDS = 300;

    private const TYPES = [
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm', 'audio/webm'],
        'mp3' => ['audio/mpeg'],
        'wav' => ['audio/wav', 'audio/x-wav'],
        'm4a' => ['audio/mp4', 'audio/x-m4a'],
    ];

    /** @return array{ok:bool,code?:string,format?:string} */
    public function validate(string $name, int $size, int $error, string $mime, string $prefix): array
    {
        if ($error !== UPLOAD_ERR_OK || $size <= 0) return ['ok' => false, 'code' => 'invalid_media'];
        if ($size > self::MAX_BYTES) return ['ok' => false, 'code' => 'media_too_large'];
        $extension = strtolower(pathinfo(basename($name), PATHINFO_EXTENSION));
        if (!isset(self::TYPES[$extension])) return ['ok' => false, 'code' => 'unsupported_media'];
        if (!in_array(strtolower($mime), self::TYPES[$extension], true)) return ['ok' => false, 'code' => 'mime_mismatch'];
        if (!$this->signatureMatches($extension, $prefix)) return ['ok' => false, 'code' => 'invalid_media'];
        return ['ok' => true, 'format' => $extension];
    }

    private function signatureMatches(string $extension, string $prefix): bool
    {
        return match ($extension) {
            'mp4', 'm4a' => strlen($prefix) >= 12 && substr($prefix, 4, 4) === 'ftyp',
            'webm' => str_starts_with($prefix, "\x1A\x45\xDF\xA3"),
            'wav' => str_starts_with($prefix, 'RIFF') && substr($prefix, 8, 4) === 'WAVE',
            'mp3' => str_starts_with($prefix, 'ID3') || (strlen($prefix) >= 2 && ord($prefix[0]) === 0xff && (ord($prefix[1]) & 0xe0) === 0xe0),
            default => false,
        };
    }
}
