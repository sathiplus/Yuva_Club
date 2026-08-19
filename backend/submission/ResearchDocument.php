<?php
declare(strict_types=1);

namespace YuvaClub\Submission;

final readonly class ResearchDocument
{
    public function __construct(
        public string $path,
        public string $storedReference,
        public string $originalName,
        public string $mimeType,
        public int $sizeBytes,
        public string $sha256,
        public string $format
    ) {
    }

    /** @return array<string, int|string|array<int, string>> */
    public function provenance(string $status = 'Pending', array $warnings = []): array
    {
        return [
            'source_file_reference' => $this->storedReference,
            'source_file_original_name' => $this->originalName,
            'source_file_mime_type' => $this->mimeType,
            'source_file_size_bytes' => $this->sizeBytes,
            'source_file_sha256' => $this->sha256,
            'document_analysis_status' => $status,
            'document_analysis_warnings' => $warnings,
            'document_format' => $this->format,
        ];
    }
}
