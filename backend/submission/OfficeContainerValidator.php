<?php
declare(strict_types=1);

namespace YuvaClub\Submission;

final class OfficeContainerValidator
{
    public const MAX_ENTRIES = 512;
    public const MAX_DECLARED_UNCOMPRESSED_BYTES = 50 * 1024 * 1024;
    public const MAX_COMPRESSION_RATIO = 100;
    private const MAX_CONTENT_TYPES_BYTES = 1024 * 1024;

    /** @return array{ok: bool, code: string} */
    public function validate(string $path, string $format): array
    {
        if (!in_array($format, ['docx', 'pptx'], true) || !is_file($path)) {
            return ['ok' => false, 'code' => 'invalid-container'];
        }

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return ['ok' => false, 'code' => 'unreadable-container'];
        }

        try {
            $size = filesize($path);
            if (!is_int($size) || $size < 22) {
                return ['ok' => false, 'code' => 'invalid-container'];
            }
            $tailLength = min($size, 65557);
            if (fseek($handle, $size - $tailLength) !== 0) {
                return ['ok' => false, 'code' => 'unreadable-container'];
            }
            $tail = fread($handle, $tailLength);
            if (!is_string($tail)) {
                return ['ok' => false, 'code' => 'unreadable-container'];
            }
            $eocdPosition = strrpos($tail, "PK\x05\x06");
            if ($eocdPosition === false || strlen($tail) - $eocdPosition < 22) {
                return ['ok' => false, 'code' => 'invalid-container'];
            }
            $eocd = unpack('vdisk/vcentralDisk/vdiskEntries/ventries/VcentralSize/VcentralOffset', substr($tail, $eocdPosition + 4, 16));
            if (!is_array($eocd)
                || (int) $eocd['disk'] !== 0
                || (int) $eocd['centralDisk'] !== 0
                || (int) $eocd['diskEntries'] !== (int) $eocd['entries']
                || (int) $eocd['entries'] < 1
                || (int) $eocd['entries'] > self::MAX_ENTRIES
                || (int) $eocd['centralOffset'] + (int) $eocd['centralSize'] > $size
            ) {
                return ['ok' => false, 'code' => 'unsafe-container'];
            }

            if (fseek($handle, (int) $eocd['centralOffset']) !== 0) {
                return ['ok' => false, 'code' => 'unreadable-container'];
            }
            $totalUncompressed = 0;
            $contentTypes = null;
            for ($index = 0; $index < (int) $eocd['entries']; $index++) {
                $header = fread($handle, 46);
                if (!is_string($header) || strlen($header) !== 46 || substr($header, 0, 4) !== "PK\x01\x02") {
                    return ['ok' => false, 'code' => 'invalid-container'];
                }
                $entry = unpack(
                    'vflags/vmethod/x8/Vcompressed/Vuncompressed/vnameLength/vextraLength/vcommentLength/x8/VlocalOffset',
                    substr($header, 8)
                );
                if (!is_array($entry)) {
                    return ['ok' => false, 'code' => 'invalid-container'];
                }
                $name = fread($handle, (int) $entry['nameLength']);
                if (!is_string($name)
                    || fseek($handle, (int) $entry['extraLength'] + (int) $entry['commentLength'], SEEK_CUR) !== 0
                ) {
                    return ['ok' => false, 'code' => 'invalid-container'];
                }
                if (((int) $entry['flags'] & 0x1) !== 0) {
                    return ['ok' => false, 'code' => 'encrypted-container'];
                }
                $compressed = (int) $entry['compressed'];
                $uncompressed = (int) $entry['uncompressed'];
                $totalUncompressed += $uncompressed;
                if ($totalUncompressed > self::MAX_DECLARED_UNCOMPRESSED_BYTES
                    || ($compressed === 0 && $uncompressed > 0)
                    || ($compressed > 0 && $uncompressed > $compressed * self::MAX_COMPRESSION_RATIO)
                ) {
                    return ['ok' => false, 'code' => 'unsafe-container'];
                }
                if ($name === '[Content_Types].xml') {
                    if ($uncompressed > self::MAX_CONTENT_TYPES_BYTES || !in_array((int) $entry['method'], [0, 8], true)) {
                        return ['ok' => false, 'code' => 'unsafe-container'];
                    }
                    $centralPosition = ftell($handle);
                    $contentTypes = $this->readEntry($handle, (int) $entry['localOffset'], $compressed, $uncompressed, (int) $entry['method']);
                    if ($centralPosition === false || fseek($handle, $centralPosition) !== 0 || $contentTypes === null) {
                        return ['ok' => false, 'code' => 'invalid-container'];
                    }
                }
            }

            if ($contentTypes === null || trim($contentTypes) === '') {
                return ['ok' => false, 'code' => 'invalid-container'];
            }
            $requiredType = $format === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'
                : 'application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml';
            return str_contains($contentTypes, $requiredType)
                ? ['ok' => true, 'code' => 'accepted']
                : ['ok' => false, 'code' => 'type-mismatch'];
        } finally {
            fclose($handle);
        }
    }

    private function readEntry($handle, int $offset, int $compressedSize, int $uncompressedSize, int $method): ?string
    {
        if (fseek($handle, $offset) !== 0) {
            return null;
        }
        $header = fread($handle, 30);
        if (!is_string($header) || strlen($header) !== 30 || substr($header, 0, 4) !== "PK\x03\x04") {
            return null;
        }
        $lengths = unpack('vnameLength/vextraLength', substr($header, 26, 4));
        if (!is_array($lengths)
            || fseek($handle, (int) $lengths['nameLength'] + (int) $lengths['extraLength'], SEEK_CUR) !== 0
        ) {
            return null;
        }
        $compressed = fread($handle, $compressedSize);
        if (!is_string($compressed) || strlen($compressed) !== $compressedSize) {
            return null;
        }
        $value = $method === 0 ? $compressed : @gzinflate($compressed, $uncompressedSize);
        return is_string($value) && strlen($value) === $uncompressedSize ? $value : null;
    }
}
