<?php
declare(strict_types=1);

namespace YuvaClub\Submission;

final class ResearchDocumentResolver
{
    private const ANALYZABLE = ['pdf', 'doc', 'docx', 'ppt', 'pptx'];
    private const EXISTING_NON_ANALYZABLE = ['jpg', 'jpeg', 'png'];

    public function __construct(
        private readonly string $uploadsRoot,
        private readonly ResearchUploadValidator $validator = new ResearchUploadValidator()
    ) {
    }

    /** @param array<string, mixed> $research */
    public function resolve(string $studentId, array $research): ?ResearchDocument
    {
        $storedValue = (string) ($research['file_stored'] ?? '');
        $originalValue = (string) ($research['file_original'] ?? '');
        $stored = basename($storedValue);
        $original = basename($originalValue);
        if ($stored === '' || $original === '') {
            return null;
        }
        if ($stored !== $storedValue || $original !== $originalValue) {
            throw new DocumentResolutionException('invalid-document-reference');
        }
        $format = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (in_array($format, self::EXISTING_NON_ANALYZABLE, true)) {
            return null;
        }
        if (!in_array($format, self::ANALYZABLE, true)) {
            throw new DocumentResolutionException('unsupported-document');
        }

        $safeStudentId = preg_replace('/[^A-Za-z0-9_-]/', '_', $studentId) ?? '';
        $studentDirectory = realpath(rtrim($this->uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeStudentId);
        $file = $studentDirectory === false ? false : realpath($studentDirectory . DIRECTORY_SEPARATOR . $stored);
        if ($studentDirectory === false || $file === false || !$this->isContained($file, $studentDirectory) || !is_file($file)) {
            throw new DocumentResolutionException('document-not-found');
        }
        $size = filesize($file);
        if (!is_int($size) || $size <= 0 || $size > ResearchUploadValidator::MAX_BYTES) {
            throw new DocumentResolutionException($size > ResearchUploadValidator::MAX_BYTES ? 'document-too-large' : 'document-empty');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        $prefix = file_get_contents($file, false, null, 0, 8);
        $validation = $this->validator->validate(
            $original,
            $size,
            UPLOAD_ERR_OK,
            is_string($mime) ? $mime : '',
            is_string($prefix) ? $prefix : '',
            $file
        );
        if (!($validation['ok'] ?? false)) {
            throw new DocumentResolutionException('document-' . ($validation['code'] ?? 'invalid'));
        }
        if ($format === 'pdf' && $this->pdfIsEncrypted($file, $size)) {
            throw new DocumentResolutionException('document-encrypted');
        }
        $hash = hash_file('sha256', $file);
        if (!is_string($hash)) {
            throw new DocumentResolutionException('document-unreadable');
        }

        return new ResearchDocument(
            $file,
            $safeStudentId . '/' . $stored,
            $original,
            is_string($mime) ? strtolower($mime) : '',
            $size,
            $hash,
            $format
        );
    }

    private function isContained(string $file, string $directory): bool
    {
        $file = str_replace('\\', '/', $file);
        $directory = rtrim(str_replace('\\', '/', $directory), '/') . '/';
        if (DIRECTORY_SEPARATOR === '\\') {
            $file = strtolower($file);
            $directory = strtolower($directory);
        }
        return str_starts_with($file, $directory);
    }

    private function pdfIsEncrypted(string $file, int $size): bool
    {
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            throw new DocumentResolutionException('document-unreadable');
        }
        try {
            $length = min($size, 65536);
            if (fseek($handle, $size - $length) !== 0) {
                throw new DocumentResolutionException('document-unreadable');
            }
            $tail = fread($handle, $length);
            return is_string($tail) && preg_match('/\/Encrypt\b/', $tail) === 1;
        } finally {
            fclose($handle);
        }
    }
}
