<?php
declare(strict_types=1);

function phase1b_contract(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__, 2);
$migration = file_get_contents($root . '/database/07-ai-mentor-phase-1b-document.azure-sql.sql') ?: '';
$rollback = file_get_contents($root . '/database/07-ai-mentor-phase-1b-document-rollback.sql') ?: '';
$provider = file_get_contents($root . '/backend/ai/OpenAiResponsesProvider.php') ?: '';
$service = file_get_contents($root . '/backend/ai/AiMentorService.php') ?: '';
$apply = file_get_contents($root . '/admin-ai-apply.php') ?: '';
$portal = file_get_contents($root . '/portal.php') ?: '';

foreach (['source_file_reference', 'source_file_original_name', 'source_file_mime_type', 'source_file_size_bytes', 'source_file_sha256', 'document_analysis_status', 'document_analysis_warnings'] as $column) {
    phase1b_contract(str_contains($migration, $column), "Migration 07 missing {$column}.");
    phase1b_contract(str_contains($rollback, $column), "Migration 07 rollback missing {$column}.");
}
foreach (['NotApplicable', 'Pending', 'Analyzed', 'Failed', 'ISJSON', '10485760'] as $contract) {
    phase1b_contract(str_contains($migration, $contract), "Migration 07 missing {$contract} contract.");
}
phase1b_contract(!str_contains($migration, 'CREATE INDEX'), 'Migration 07 must not add an unused SHA index.');
phase1b_contract(str_contains($provider, "'type' => 'input_file'") && str_contains($provider, "'file_data'"), 'Provider must use native private input_file data.');
phase1b_contract(!str_contains($provider, '/v1/files') && !str_contains($provider, 'file_url'), 'Provider must not create persistent files or public URLs.');
phase1b_contract(str_contains($service, 'DOCUMENT_REVIEW_VERSION') && str_contains($service, '$document->sha256'), 'Service must use v2 prompt and document provenance.');
phase1b_contract(str_contains($apply, 'research_document_for_student'), 'Apply must recalculate the current document revision.');
phase1b_contract(str_contains($portal, "=== 'Applied'") && str_contains($portal, 'Your uploaded document was included'), 'Student document statement must remain Applied-only.');

fwrite(STDOUT, "PASS Phase 1B migration, provider, stale, privacy, and visibility contracts\n");
