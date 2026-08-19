<?php
declare(strict_types=1);

use YuvaClub\AI\AiMentorService;
use YuvaClub\AI\AiPromptCatalog;
use YuvaClub\AI\AiReviewRepository;
use YuvaClub\AI\AiReviewValidator;
use YuvaClub\AI\DocumentAwareAiProvider;
use YuvaClub\AI\OpenAiResponsesProvider;
use YuvaClub\Submission\DocumentResolutionException;
use YuvaClub\Submission\OfficeContainerValidator;
use YuvaClub\Submission\ResearchDocument;
use YuvaClub\Submission\ResearchDocumentResolver;
use YuvaClub\Submission\ResearchUploadValidator;

$root = dirname(__DIR__, 2);
foreach ([
    '/backend/ai/AiProvider.php',
    '/backend/ai/DocumentAwareAiProvider.php',
    '/backend/ai/AiReviewStore.php',
    '/backend/ai/AiPromptCatalog.php',
    '/backend/ai/AiReviewValidator.php',
    '/backend/ai/AiReviewRepository.php',
    '/backend/ai/OpenAiResponsesProvider.php',
    '/backend/ai/AiMentorService.php',
    '/backend/submission/ResearchDocument.php',
    '/backend/submission/DocumentResolutionException.php',
    '/backend/submission/OfficeContainerValidator.php',
    '/backend/submission/ResearchUploadValidator.php',
    '/backend/submission/ResearchDocumentResolver.php',
] as $dependency) {
    require_once $root . $dependency;
}

function phase1b_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    fwrite(STDOUT, "PASS {$message}\n");
}

/** @param array<string, string> $entries */
function phase1b_zip(string $path, array $entries, int $flags = 0): void
{
    $local = '';
    $central = '';
    foreach ($entries as $name => $contents) {
        $offset = strlen($local);
        $crc = crc32($contents);
        $size = strlen($contents);
        $local .= pack('VvvvvvVVVvv', 0x04034b50, 20, $flags, 0, 0, 0, $crc, $size, $size, strlen($name), 0)
            . $name . $contents;
        $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, $flags, 0, 0, 0, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $offset)
            . $name;
    }
    $archive = $local . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($entries), count($entries), strlen($central), strlen($local), 0);
    file_put_contents($path, $archive);
}

function phase1b_review(): array
{
    return [
        'research_quality' => 18,
        'presentation_structure' => 17,
        'topic_understanding' => 16,
        'discussion_questions' => 12,
        'leadership_lesson' => 13,
        'effort_and_readiness' => 9,
        'total_points' => 85,
        'summary' => 'The typed research and uploaded document support the review.',
        'strengths' => ['Typed evidence', 'Document evidence'],
        'improvements' => ['Add a citation'],
        'communication_skills' => 'The structure is clear.',
        'leadership_milestones' => 'The work connects ideas to action.',
        'suggested_tokens' => 4,
        'recommended_next_step' => 'Add one cited example.',
        'admin_notes' => 'Confirm the cited source.',
    ];
}

final class Phase1bProvider implements DocumentAwareAiProvider
{
    public string $prompt = '';
    public ?ResearchDocument $document = null;
    public int $textCalls = 0;
    public int $documentCalls = 0;

    public function __construct(private readonly bool $fail = false)
    {
    }

    public function generateStructuredReview(string $prompt): array
    {
        $this->textCalls++;
        return ['ok' => true, 'output' => phase1b_review()];
    }

    public function generateStructuredDocumentReview(string $prompt, ResearchDocument $document): array
    {
        $this->documentCalls++;
        $this->prompt = $prompt;
        $this->document = $document;
        return $this->fail
            ? ['ok' => false, 'error' => 'Sanitized provider rejection.']
            : ['ok' => true, 'output' => phase1b_review()];
    }
}

$temporary = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yuva-phase1b-' . bin2hex(random_bytes(6));
mkdir($temporary, 0700, true);
$studentDirectory = $temporary . DIRECTORY_SEPARATOR . 'YC-TEST-1';
mkdir($studentDirectory, 0700, true);

try {
    $docx = $studentDirectory . DIRECTORY_SEPARATOR . 'research.docx';
    phase1b_zip($docx, [
        '[Content_Types].xml' => '<Types><Override ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
        'word/document.xml' => '<document>DOCX_EVIDENCE</document>',
    ]);
    $pptx = $studentDirectory . DIRECTORY_SEPARATOR . 'slides.pptx';
    phase1b_zip($pptx, [
        '[Content_Types].xml' => '<Types><Override ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/></Types>',
        'ppt/slides/slide1.xml' => '<slide>PPTX_EVIDENCE</slide>',
    ]);
    $genericZip = $studentDirectory . DIRECTORY_SEPARATOR . 'fake.docx';
    phase1b_zip($genericZip, ['hello.txt' => 'not an office package']);
    $encrypted = $studentDirectory . DIRECTORY_SEPARATOR . 'encrypted.docx';
    phase1b_zip($encrypted, ['[Content_Types].xml' => 'encrypted'], 1);
    $encryptedPdf = $studentDirectory . DIRECTORY_SEPARATOR . 'encrypted.pdf';
    file_put_contents($encryptedPdf, "%PDF-1.7\n1 0 obj<</Encrypt 2 0 R>>endobj\n%%EOF");

    $container = new OfficeContainerValidator();
    phase1b_assert($container->validate($docx, 'docx')['ok'], 'DOCX OOXML identity accepted');
    phase1b_assert($container->validate($pptx, 'pptx')['ok'], 'PPTX OOXML identity accepted');
    phase1b_assert(!$container->validate($genericZip, 'docx')['ok'], 'renamed generic ZIP rejected');
    phase1b_assert(($container->validate($encrypted, 'docx')['code'] ?? '') === 'encrypted-container', 'encrypted OOXML rejected');
    phase1b_assert(!$container->validate($docx, 'pptx')['ok'], 'OOXML declared-type mismatch rejected');

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($docx);
    $resolver = new ResearchDocumentResolver($temporary);
    $resolved = $resolver->resolve('YC-TEST-1', [
        'file_original' => 'research.docx',
        'file_stored' => 'research.docx',
    ]);
    phase1b_assert($resolved instanceof ResearchDocument && $resolved->sha256 === hash_file('sha256', $docx), 'owned document resolves with immutable SHA-256');
    phase1b_assert($resolved->mimeType === strtolower((string) $mime), 'document MIME recorded from server detection');

    try {
        $resolver->resolve('YC-TEST-2', ['file_original' => 'research.docx', 'file_stored' => '../YC-TEST-1/research.docx']);
        phase1b_assert(false, 'cross-student resolution rejected');
    } catch (DocumentResolutionException) {
        phase1b_assert(true, 'cross-student resolution rejected');
    }
    try {
        $resolver->resolve('YC-TEST-1', ['file_original' => 'research.docx', 'file_stored' => '../../research.docx']);
        phase1b_assert(false, 'path traversal rejected');
    } catch (DocumentResolutionException) {
        phase1b_assert(true, 'path traversal rejected');
    }
    phase1b_assert($resolver->resolve('YC-TEST-1', ['file_original' => 'photo.png', 'file_stored' => 'photo.png']) === null, 'existing image upload is not a Phase 1B analysis input');
    try {
        $resolver->resolve('YC-TEST-1', ['file_original' => 'encrypted.pdf', 'file_stored' => 'encrypted.pdf']);
        phase1b_assert(false, 'deterministically encrypted PDF rejected');
    } catch (DocumentResolutionException $error) {
        phase1b_assert($error->safeCode === 'document-encrypted', 'deterministically encrypted PDF rejected');
    }

    $provider = new Phase1bProvider();
    $records = [];
    $repository = new AiReviewRepository(
        static fn(): array => $records,
        static function (array $updated) use (&$records): void { $records = $updated; }
    );
    $service = new AiMentorService($provider, new AiPromptCatalog(), new AiReviewValidator(), $repository);
    $selection = ['topic_category' => 'Science', 'topic_title' => 'Clean Water'];
    $research = [
        'research_notes' => 'TYPED_RESEARCH_EVIDENCE',
        'sources_used' => 'School library',
        'presentation_outline' => 'Problem, evidence, action',
        'prepared_questions' => 'How can students help?',
    ];
    $draft = $service->createDraft('YC-TEST-1', ['Student Preferred Name' => 'Test'], $selection, $research, $resolved);
    phase1b_assert($draft['status'] === 'Draft' && $draft['prompt_version'] === AiPromptCatalog::DOCUMENT_REVIEW_VERSION, 'document review creates existing structured Draft with v2 prompt');
    phase1b_assert($provider->documentCalls === 1 && $provider->textCalls === 0, 'document route calls only document-aware provider method');
    phase1b_assert(str_contains($provider->prompt, 'TYPED_RESEARCH_EVIDENCE') && str_contains($provider->prompt, 'untrusted student-provided material'), 'typed research and prompt-injection boundary reach provider');
    phase1b_assert(($records['YC-TEST-1']['document_analysis_status'] ?? '') === 'Analyzed', 'analyzed provenance persists separately from structured review');

    $oldHash = AiMentorService::sourceRevisionHash($selection, $research, $resolved);
    $changed = new ResearchDocument($resolved->path, $resolved->storedReference, $resolved->originalName, $resolved->mimeType, $resolved->sizeBytes, str_repeat('a', 64), $resolved->format);
    phase1b_assert($oldHash !== AiMentorService::sourceRevisionHash($selection, $research, $changed), 'document replacement changes source revision hash');

    $textProvider = new Phase1bProvider();
    $textRecords = [];
    $textService = new AiMentorService(
        $textProvider,
        new AiPromptCatalog(),
        new AiReviewValidator(),
        new AiReviewRepository(static fn(): array => $textRecords, static function (array $updated) use (&$textRecords): void { $textRecords = $updated; })
    );
    $textDraft = $textService->createDraft('YC-TEXT', [], $selection, $research);
    phase1b_assert($textDraft['prompt_version'] === AiPromptCatalog::RESEARCH_REVIEW_VERSION && $textProvider->textCalls === 1, 'Phase 1A text-only path remains unchanged');

    $requestProvider = new OpenAiResponsesProvider('not-used', 'gpt-4.1-mini');
    $payload = $requestProvider->buildRequestPayload('typed context', 'research.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', base64_encode('DOC_BYTES'));
    $content = $payload['input'][1]['content'] ?? [];
    phase1b_assert(($content[0]['type'] ?? '') === 'input_text' && ($content[1]['type'] ?? '') === 'input_file', 'Responses request separates typed context and input_file');
    phase1b_assert(str_starts_with((string) ($content[1]['file_data'] ?? ''), 'data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,'), 'Responses request uses private Base64 file_data');
    phase1b_assert(!str_contains(file_get_contents($root . '/backend/ai/OpenAiResponsesProvider.php') ?: '', 'error_log('), 'provider does not log Base64, document content, key, or request body');

    $failedRecords = [];
    $failedService = new AiMentorService(
        new Phase1bProvider(true),
        new AiPromptCatalog(),
        new AiReviewValidator(),
        new AiReviewRepository(static fn(): array => $failedRecords, static function (array $updated) use (&$failedRecords): void { $failedRecords = $updated; })
    );
    $failedDraft = $failedService->createDraft('YC-FAIL', [], $selection, $research, $resolved);
    phase1b_assert(
        $failedDraft['status'] === 'Failed'
        && $failedDraft['document_analysis_status'] === 'Failed'
        && ($failedDraft['review'] ?? []) === []
        && ($failedDraft['error'] ?? '') === 'Document analysis could not be completed.',
        'provider rejection persists sanitized Failed with no partial Draft'
    );

    $validator = new ResearchUploadValidator();
    phase1b_assert($validator->validate('legacy.doc', 1024, UPLOAD_ERR_OK, 'application/msword', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")['ok'], 'legacy DOC remains accepted for native provider analysis');
    phase1b_assert($validator->validate('legacy.ppt', 1024, UPLOAD_ERR_OK, 'application/vnd.ms-powerpoint', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")['ok'], 'legacy PPT remains accepted for native provider analysis');

    fwrite(STDOUT, "PASS Phase 1B document analysis suite\n");
} finally {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($temporary);
}
