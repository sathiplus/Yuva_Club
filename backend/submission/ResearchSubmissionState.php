<?php
declare(strict_types=1);

namespace YuvaClub\Submission;

final class ResearchSubmissionState
{
    public const NO_SUBMISSION = 'no-submission';
    public const DRAFT_INCOMPLETE = 'draft-incomplete';
    public const SUBMISSION_RECEIVED = 'submission-received';
    public const REVIEW_NOT_STARTED = 'review-not-started';
    public const REVIEW_PROCESSING = 'review-processing';
    public const REVIEW_PENDING_APPROVAL = 'review-pending-approval';
    public const REVIEW_APPROVED = 'review-approved';
    public const REVIEW_UNAVAILABLE = 'review-unavailable';
    public const NEEDS_RESUBMISSION = 'needs-resubmission';
    public const UNSUPPORTED_FILE = 'unsupported-file';
    public const UPLOAD_FAILURE = 'upload-failure';

    /** @var array<int, string> */
    private const REQUIRED_FIELDS = [
        'research_notes',
        'sources_used',
        'presentation_outline',
        'prepared_questions',
    ];

    /**
     * @param array<string, mixed> $research
     * @param array<string, mixed> $review
     */
    public static function derive(
        array $research,
        array $review,
        bool $justSubmitted = false,
        string $uploadOutcome = ''
    ): string {
        if (in_array($uploadOutcome, ['unsupported', 'type-mismatch', 'too-large'], true)) {
            return self::UNSUPPORTED_FILE;
        }
        if ($uploadOutcome === 'upload-failed') {
            return self::UPLOAD_FAILURE;
        }
        if ($research === []) {
            return self::NO_SUBMISSION;
        }
        foreach (self::REQUIRED_FIELDS as $field) {
            if (trim((string) ($research[$field] ?? '')) === '') {
                return self::DRAFT_INCOMPLETE;
            }
        }
        if ($justSubmitted) {
            return self::SUBMISSION_RECEIVED;
        }

        $reviewStatus = trim((string) ($review['status'] ?? ''));
        if ($reviewStatus === 'Applied by Admin') {
            return self::REVIEW_APPROVED;
        }
        if (
            stripos($reviewStatus, 'resubmi') !== false
            || stripos($reviewStatus, 'rejected') !== false
        ) {
            return self::NEEDS_RESUBMISSION;
        }
        if (
            $reviewStatus === 'Needs Setup'
            || trim((string) ($review['error'] ?? '')) !== ''
        ) {
            return self::REVIEW_UNAVAILABLE;
        }
        if (
            $reviewStatus === 'Processing'
            || $reviewStatus === 'Review Processing'
        ) {
            return self::REVIEW_PROCESSING;
        }
        if ($reviewStatus === 'Draft - Pending Admin Approval') {
            return self::REVIEW_PENDING_APPROVAL;
        }
        return self::REVIEW_NOT_STARTED;
    }

    /** @return array{eyebrow: string, title: string, body: string, tone: string} */
    public static function presentation(string $state): array
    {
        return match ($state) {
            self::NO_SUBMISSION => [
                'eyebrow' => 'No submission',
                'title' => 'Your preparation starts here',
                'body' => 'Complete your research notes, sources, outline, and prepared questions when you are ready.',
                'tone' => 'neutral',
            ],
            self::DRAFT_INCOMPLETE => [
                'eyebrow' => 'Draft incomplete',
                'title' => 'Finish the required preparation',
                'body' => 'Review each required research field, then submit again.',
                'tone' => 'attention',
            ],
            self::SUBMISSION_RECEIVED => [
                'eyebrow' => 'Submission received',
                'title' => 'Your preparation was saved',
                'body' => 'The existing YUVA review workflow can now begin. No completion time is promised.',
                'tone' => 'success',
            ],
            self::REVIEW_NOT_STARTED => [
                'eyebrow' => 'Review not started',
                'title' => 'Your preparation is waiting for review',
                'body' => 'No review has been created yet. You can continue practicing while you wait.',
                'tone' => 'neutral',
            ],
            self::REVIEW_PROCESSING => [
                'eyebrow' => 'Review processing',
                'title' => 'Your review is being prepared',
                'body' => 'The current record says processing is underway. No automated progress or completion estimate is available.',
                'tone' => 'progress',
            ],
            self::REVIEW_PENDING_APPROVAL => [
                'eyebrow' => 'Pending administrator approval',
                'title' => 'Your guidance is being checked',
                'body' => 'A YUVA Club administrator must approve the review before you can see it.',
                'tone' => 'progress',
            ],
            self::REVIEW_APPROVED => [
                'eyebrow' => 'Review approved',
                'title' => 'Your approved guidance is ready',
                'body' => 'Open AI Mentor to read the review approved by a YUVA Club administrator.',
                'tone' => 'success',
            ],
            self::REVIEW_UNAVAILABLE => [
                'eyebrow' => 'Review unavailable',
                'title' => 'Your review cannot be shown right now',
                'body' => 'Your submission remains saved. Please check back later or ask the YUVA Club team for help.',
                'tone' => 'attention',
            ],
            self::NEEDS_RESUBMISSION => [
                'eyebrow' => 'Resubmission needed',
                'title' => 'Review your preparation and submit again',
                'body' => 'Update the requested research information, then resubmit through this workspace.',
                'tone' => 'attention',
            ],
            self::UNSUPPORTED_FILE => [
                'eyebrow' => 'File not accepted',
                'title' => 'Choose a supported file and try again',
                'body' => 'Use PDF, PowerPoint, Word, JPG, or PNG files no larger than 10 MB.',
                'tone' => 'error',
            ],
            self::UPLOAD_FAILURE => [
                'eyebrow' => 'Upload did not finish',
                'title' => 'Your file could not be saved',
                'body' => 'Your written preparation remains available. Choose the file again and retry.',
                'tone' => 'error',
            ],
            default => self::presentation(self::REVIEW_UNAVAILABLE),
        };
    }
}
