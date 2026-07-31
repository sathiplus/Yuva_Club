<?php
declare(strict_types=1);

use YuvaClub\Submission\ResearchSubmissionState;
use YuvaClub\Submission\ResearchUploadValidator;

require_once __DIR__ . '/../../backend/submission/ResearchUploadValidator.php';
require_once __DIR__ . '/../../backend/submission/ResearchSubmissionState.php';

function submission_test(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL {$message}\n");
        exit(1);
    }
    fwrite(STDOUT, "PASS {$message}\n");
}

$validator = new ResearchUploadValidator();
$accepted = [
    ['notes.pdf', 'application/pdf', "%PDF-1.7"],
    ['slides.ppt', 'application/vnd.ms-powerpoint', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"],
    ['slides.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', "PK\x03\x04"],
    ['notes.doc', 'application/msword', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"],
    ['notes.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', "PK\x03\x04"],
    ['photo.jpg', 'image/jpeg', "\xFF\xD8\xFF\xE0"],
    ['photo.jpeg', 'image/jpeg', "\xFF\xD8\xFF\xE1"],
    ['diagram.png', 'image/png', "\x89PNG\r\n\x1A\n"],
];
foreach ($accepted as [$name, $mime, $prefix]) {
    submission_test(
        $validator->validate($name, 1024, UPLOAD_ERR_OK, $mime, $prefix)['ok'],
        "{$name} accepted with matching MIME and signature"
    );
}

submission_test(
    !$validator->validate('script.php', 100, UPLOAD_ERR_OK, 'text/x-php', '<?php')['ok'],
    'unsupported extension rejected'
);
submission_test(
    ($validator->validate('photo.jpg', 100, UPLOAD_ERR_OK, 'image/png', "\x89PNG\r\n\x1A\n")['code'] ?? '') === 'type-mismatch',
    'extension and MIME mismatch rejected'
);
submission_test(
    ($validator->validate('notes.pdf', ResearchUploadValidator::MAX_BYTES + 1, UPLOAD_ERR_OK, 'application/pdf', '%PDF-')['code'] ?? '') === 'too-large',
    'file above explicit application limit rejected'
);
submission_test(
    ($validator->validate('notes.pdf', 0, UPLOAD_ERR_CANT_WRITE, '', '')['code'] ?? '') === 'upload-failed',
    'runtime upload failure rejected'
);

$completeResearch = [
    'research_notes' => 'Notes',
    'sources_used' => 'Sources',
    'presentation_outline' => 'Outline',
    'prepared_questions' => 'Questions',
];
$stateCases = [
    ResearchSubmissionState::NO_SUBMISSION => [[], [], false, ''],
    ResearchSubmissionState::DRAFT_INCOMPLETE => [['research_notes' => 'Draft'], [], false, ''],
    ResearchSubmissionState::SUBMISSION_RECEIVED => [$completeResearch, [], true, ''],
    ResearchSubmissionState::REVIEW_NOT_STARTED => [$completeResearch, [], false, ''],
    ResearchSubmissionState::REVIEW_PROCESSING => [$completeResearch, ['status' => 'Processing'], false, ''],
    ResearchSubmissionState::REVIEW_PENDING_APPROVAL => [$completeResearch, ['status' => 'Draft - Pending Admin Approval'], false, ''],
    ResearchSubmissionState::REVIEW_APPROVED => [$completeResearch, ['status' => 'Applied by Admin'], false, ''],
    ResearchSubmissionState::REVIEW_UNAVAILABLE => [$completeResearch, ['status' => 'Needs Setup'], false, ''],
    ResearchSubmissionState::NEEDS_RESUBMISSION => [$completeResearch, ['status' => 'Needs Resubmission'], false, ''],
    ResearchSubmissionState::UNSUPPORTED_FILE => [$completeResearch, [], false, 'type-mismatch'],
    ResearchSubmissionState::UPLOAD_FAILURE => [$completeResearch, [], false, 'upload-failed'],
];
foreach ($stateCases as $expected => [$research, $review, $justSubmitted, $uploadOutcome]) {
    submission_test(
        ResearchSubmissionState::derive($research, $review, $justSubmitted, $uploadOutcome) === $expected,
        "{$expected} submission state"
    );
    $presentation = ResearchSubmissionState::presentation($expected);
    submission_test(
        $presentation['title'] !== '' && $presentation['body'] !== '',
        "{$expected} has student-facing presentation"
    );
}

submission_test(
    ResearchSubmissionState::derive(
        $completeResearch,
        ['status' => 'Stale - Research Changed'],
        false,
        ''
    ) === ResearchSubmissionState::REVIEW_NOT_STARTED,
    'stale prior review is not treated as approved'
);

fwrite(STDOUT, "PASS research submission foundation suite\n");
