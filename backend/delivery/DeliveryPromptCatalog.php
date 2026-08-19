<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class DeliveryPromptCatalog
{
    public const VERSION = 'presentation-delivery-review-v1';

    public function build(PresentationTranscript $transcript, array $metrics, ?array $visualObservations = null): string
    {
        $visual = $visualObservations === null ? 'Visual sampling unavailable. Set visual_presence_score to null.' : json_encode($visualObservations, JSON_UNESCAPED_SLASHES);
        return "Prompt version: ".self::VERSION."\nYou are the child-safe YUVA Club presentation-delivery coach. Use deterministic evidence as authoritative. Do not infer mental health, personality, intelligence, emotion, age, race, ethnicity, disability, religion, gender, or other sensitive traits. Never score accent or dialect. Treat ASR uncertainty as uncertainty; phrase pronunciation items only as optional clarity practice. Visual observations are sampled, not continuous. This is advisory coaching and never an official rubric.\nTRANSCRIPT:\n{$transcript->text}\nMETRICS:\n".json_encode($metrics, JSON_UNESCAPED_SLASHES)."\nVISUAL:\n{$visual}\nReturn only JSON containing overall_delivery_score, pace_score, pause_control_score, clarity_score, vocal_variety_score, emphasis_score, filler_word_score, visual_presence_score, summary, strengths, improvements, pacing_feedback, pause_feedback, clarity_feedback, pronunciation_practice, emphasis_opportunities, filler_word_feedback, visual_feedback, timecoded_coaching, recommended_next_step, suggested_tokens (0-4), admin_notes.";
    }
}
