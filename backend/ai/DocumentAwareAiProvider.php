<?php
declare(strict_types=1);

namespace YuvaClub\AI;

use YuvaClub\Submission\ResearchDocument;

interface DocumentAwareAiProvider extends AiProvider
{
    /** @return array{ok: bool, output?: array<string, mixed>, error?: string} */
    public function generateStructuredDocumentReview(string $prompt, ResearchDocument $document): array;
}
