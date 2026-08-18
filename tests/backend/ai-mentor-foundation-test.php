<?php
declare(strict_types=1);

use YuvaClub\AI\AiMentorService;
use YuvaClub\AI\AiPromptCatalog;
use YuvaClub\AI\AiProvider;
use YuvaClub\AI\AiReviewRepository;
use YuvaClub\AI\AiReviewState;
use YuvaClub\AI\AiReviewValidator;

require_once __DIR__ . '/../../backend/config.php';
require_once __DIR__ . '/../../backend/ai/AiProvider.php';
require_once __DIR__ . '/../../backend/ai/AiPromptCatalog.php';
require_once __DIR__ . '/../../backend/ai/AiReviewValidator.php';
require_once __DIR__ . '/../../backend/ai/AiReviewState.php';
require_once __DIR__ . '/../../backend/ai/AiReviewStore.php';
require_once __DIR__ . '/../../backend/ai/AiReviewRepository.php';
require_once __DIR__ . '/../../backend/ai/AiMentorService.php';

function ai_foundation_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return array<string, mixed> */
function ai_foundation_valid_review(): array
{
    return [
        'research_quality' => 18,
        'presentation_structure' => 17,
        'topic_understanding' => 16,
        'discussion_questions' => 12,
        'leadership_lesson' => 13,
        'effort_and_readiness' => 9,
        'total_points' => 85,
        'summary' => 'A thoughtful and well-organized submission.',
        'strengths' => ['Clear purpose', 'Good sources', 'Strong outline'],
        'improvements' => ['Add one example', 'Refine the opening'],
        'communication_skills' => 'The outline supports clear delivery.',
        'leadership_milestones' => 'The student is connecting ideas to action.',
        'suggested_tokens' => 3,
        'recommended_next_step' => 'Add one concrete example to the opening.',
        'admin_notes' => 'Review the source quality before approval.',
    ];
}

final class AiFoundationProvider implements AiProvider
{
    public string $prompt = '';

    /** @param array<string, mixed> $output */
    public function __construct(private readonly array $output)
    {
    }

    public function generateStructuredReview(string $prompt): array
    {
        $this->prompt = $prompt;
        return ['ok' => true, 'output' => $this->output];
    }
}

$records = [];
$repository = new AiReviewRepository(
    static function () use (&$records): array {
        return $records;
    },
    static function (array $updated) use (&$records): void {
        $records = $updated;
    }
);
$provider = new AiFoundationProvider(ai_foundation_valid_review());
$service = new AiMentorService(
    $provider,
    new AiPromptCatalog(),
    new AiReviewValidator(),
    $repository
);

$draft = $service->createDraft(
    'YC2026001',
    [
        'Student Preferred Name' => 'Asha',
        'Student First Name' => 'Asha',
    ],
    [
        'topic_category' => 'Leadership',
        'topic_title' => 'Courage',
    ],
    [
        'research_notes' => 'Research notes',
        'sources_used' => 'Sources',
        'presentation_outline' => 'Outline',
        'prepared_questions' => 'Questions',
    ]
);

ai_foundation_assert(
    $draft['status'] === 'Draft'
    && $draft['prompt_version'] === AiPromptCatalog::RESEARCH_REVIEW_VERSION
    && $repository->find('YC2026001')['review']['total_points'] === 85,
    'Service must persist a backward-compatible draft with prompt version.'
);
ai_foundation_assert(
    str_contains(
        $provider->prompt,
        'Prompt version: ' . AiPromptCatalog::RESEARCH_REVIEW_VERSION
    ),
    'Every provider request must contain the prompt version.'
);

$failedRecords = [];
$failedRepository = new AiReviewRepository(
    static function () use (&$failedRecords): array { return $failedRecords; },
    static function (array $updated) use (&$failedRecords): void { $failedRecords = $updated; }
);
$malformed = ai_foundation_valid_review();
$malformed['total_points'] = 100;
$failedDraft = (new AiMentorService(
    new AiFoundationProvider($malformed),
    new AiPromptCatalog(),
    new AiReviewValidator(),
    $failedRepository
))->createDraft('YC2026002', [], [], []);
ai_foundation_assert(
    $failedDraft['status'] === 'Failed'
    && AiReviewState::fromRuntime(true, $failedDraft) === AiReviewState::UNAVAILABLE,
    'Malformed provider output must persist Failed and remain student-invisible.'
);

$validator = new AiReviewValidator();
ai_foundation_assert(
    ($validator->validate(ai_foundation_valid_review())['ok'] ?? false) === true,
    'Validator must accept the current valid review contract.'
);
$invalid = ai_foundation_valid_review();
$invalid['research_quality'] = 21;
ai_foundation_assert(
    ($validator->validate($invalid)['ok'] ?? true) === false,
    'Validator must reject out-of-range category scores.'
);
$invalid = ai_foundation_valid_review();
unset($invalid['strengths']);
ai_foundation_assert(
    ($validator->validate($invalid)['ok'] ?? true) === false,
    'Validator must reject missing required feedback arrays.'
);

$states = [
    AiReviewState::NO_RESEARCH => [false, []],
    AiReviewState::NOT_CREATED => [true, []],
    AiReviewState::AWAITING_APPROVAL => [
        true,
        ['status' => 'Draft'],
    ],
    AiReviewState::APPROVED => [true, ['status' => 'Applied']],
    AiReviewState::UNAVAILABLE => [
        true,
        ['status' => 'Failed', 'error' => 'Provider unavailable'],
    ],
];
foreach ($states as $expected => [$hasResearch, $record]) {
    ai_foundation_assert(
        AiReviewState::fromRuntime($hasResearch, $record) === $expected,
        'AI review runtime state did not preserve ' . $expected . '.'
    );
}

$featureEnvironment = [
    'AI_MENTOR_FOUNDATION_ENABLED' => 'true',
    'AI_MENTOR_COACH_ME_ENABLED' => 'false',
    'AI_MENTOR_MEDIA_ANALYSIS_ENABLED' => 'false',
    'AI_MENTOR_WEEKLY_REPORTS_ENABLED' => 'false',
    'AI_MENTOR_GUIDED_MENTOR_ENABLED' => 'false',
    'AI_MENTOR_PREMIUM_ENTITLEMENT_ENABLED' => 'false',
];
$originalEnvironment = [];
try {
    foreach ($featureEnvironment as $name => $value) {
        $originalEnvironment[$name] = getenv($name);
        putenv($name . '=' . $value);
    }
    ai_foundation_assert(
        ai_mentor_feature_enabled('foundation_enabled') === true,
        'AI Mentor foundation flag must be available.'
    );
    foreach ([
        'coach_me_enabled',
        'media_analysis_enabled',
        'weekly_reports_enabled',
        'guided_mentor_enabled',
        'premium_entitlement_enabled',
    ] as $capability) {
        ai_foundation_assert(
            ai_mentor_feature_enabled($capability) === false,
            'Future capability must default disabled: ' . $capability
        );
    }
} finally {
    foreach ($originalEnvironment as $name => $value) {
        putenv($value === false ? $name : $name . '=' . $value);
    }
}

fwrite(STDOUT, "PASS AI provider and service abstraction\n");
fwrite(STDOUT, "PASS versioned research-review prompt\n");
fwrite(STDOUT, "PASS strict AI review validation\n");
fwrite(STDOUT, "PASS backward-compatible review repository\n");
fwrite(STDOUT, "PASS AI review state model\n");
fwrite(STDOUT, "PASS future AI Mentor capabilities default disabled\n");
