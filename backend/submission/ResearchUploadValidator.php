<?php
declare(strict_types=1);

namespace YuvaClub\Submission;

final class ResearchUploadValidator
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** @var array<string, array<int, string>> */
    private const MIME_BY_EXTENSION = [
        'pdf' => ['application/pdf'],
        'ppt' => [
            'application/vnd.ms-powerpoint',
            'application/vnd.ms-office',
            'application/x-ole-storage',
        ],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
        ],
        'doc' => [
            'application/msword',
            'application/vnd.ms-office',
            'application/x-ole-storage',
        ],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];

    /**
     * @return array{ok: bool, code: string, extension?: string}
     */
    public function validate(
        string $originalName,
        int $size,
        int $uploadError,
        string $detectedMime,
        string $prefix
    ): array {
        if ($uploadError !== UPLOAD_ERR_OK) {
            if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
                return ['ok' => false, 'code' => 'too-large'];
            }
            return ['ok' => false, 'code' => 'upload-failed'];
        }

        if ($size <= 0) {
            return ['ok' => false, 'code' => 'upload-failed'];
        }
        if ($size > self::MAX_BYTES) {
            return ['ok' => false, 'code' => 'too-large'];
        }

        $extension = strtolower(pathinfo(basename($originalName), PATHINFO_EXTENSION));
        if (!array_key_exists($extension, self::MIME_BY_EXTENSION)) {
            return ['ok' => false, 'code' => 'unsupported'];
        }

        $mime = strtolower(trim($detectedMime));
        if (!in_array($mime, self::MIME_BY_EXTENSION[$extension], true)) {
            return ['ok' => false, 'code' => 'type-mismatch'];
        }

        if (!$this->signatureMatches($extension, $prefix)) {
            return ['ok' => false, 'code' => 'type-mismatch'];
        }

        return ['ok' => true, 'code' => 'accepted', 'extension' => $extension];
    }

    private function signatureMatches(string $extension, string $prefix): bool
    {
        return match ($extension) {
            'pdf' => str_starts_with($prefix, '%PDF-'),
            'jpg', 'jpeg' => str_starts_with($prefix, "\xFF\xD8\xFF"),
            'png' => str_starts_with($prefix, "\x89PNG\r\n\x1A\n"),
            'ppt', 'doc' => str_starts_with($prefix, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"),
            'pptx', 'docx' => str_starts_with($prefix, "PK\x03\x04")
                || str_starts_with($prefix, "PK\x05\x06")
                || str_starts_with($prefix, "PK\x07\x08"),
            default => false,
        };
    }
}
