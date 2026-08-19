<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class DeliveryMetricsCalculator
{
    private const FILLERS = ['um','uh','erm','hmm','like','basically','actually','literally'];

    public function calculate(PresentationTranscript $transcript): array
    {
        $tokens = preg_split('/[^\pL\pN\']+/u', strtolower($transcript->text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $count = count($tokens);
        $duration = max(0.001, $transcript->durationSeconds);
        $fillers = array_count_values(array_values(array_filter($tokens, static fn(string $word): bool => in_array($word, self::FILLERS, true))));
        $pauses = [];
        $previousEnd = null;
        foreach ($transcript->segments as $segment) {
            $start = (float) ($segment['start'] ?? 0);
            $end = (float) ($segment['end'] ?? $start);
            if ($previousEnd !== null && $start - $previousEnd >= 1.0) $pauses[] = round($start - $previousEnd, 3);
            $previousEnd = max($previousEnd ?? 0, $end);
        }
        $longSentences = array_values(array_filter(preg_split('/(?<=[.!?])\s+/u', trim($transcript->text)) ?: [], static fn(string $s): bool => str_word_count($s) > 30));
        return [
            'duration_seconds' => round($duration, 3),
            'total_spoken_words' => $count,
            'words_per_minute' => round($count * 60 / $duration, 1),
            'speaking_time_estimate_seconds' => round(array_sum(array_map(static fn(array $s): float => max(0, (float)($s['end'] ?? 0) - (float)($s['start'] ?? 0)), $transcript->segments)), 3),
            'pause_count' => count($pauses),
            'pause_durations_seconds' => $pauses,
            'longest_pause_seconds' => $pauses === [] ? 0 : max($pauses),
            'average_pause_seconds' => $pauses === [] ? 0 : round(array_sum($pauses) / count($pauses), 3),
            'filler_word_count' => array_sum($fillers),
            'fillers_per_100_words' => $count === 0 ? 0 : round(array_sum($fillers) * 100 / $count, 2),
            'common_filler_terms' => $fillers,
            'unusually_long_sentences' => array_slice($longSentences, 0, 5),
            'timecoded_evidence' => $this->timecodedEvidence($transcript, $pauses),
        ];
    }

    private function timecodedEvidence(PresentationTranscript $transcript, array $pauses): array
    {
        $evidence = [];
        $previousEnd = null;
        foreach ($transcript->segments as $segment) {
            $start = (float) ($segment['start'] ?? 0);
            if ($previousEnd !== null && $start - $previousEnd >= 1.0) $evidence[] = ['start_seconds' => $previousEnd, 'end_seconds' => $start, 'category' => 'pause', 'observation' => 'Measurable pause'];
            $previousEnd = (float) ($segment['end'] ?? $start);
        }
        foreach ($transcript->words as $word) {
            if (in_array(strtolower(trim((string)($word['word'] ?? ''))), self::FILLERS, true)) $evidence[] = ['start_seconds' => (float)($word['start'] ?? 0), 'end_seconds' => (float)($word['end'] ?? $word['start'] ?? 0), 'category' => 'filler', 'observation' => (string)$word['word']];
        }
        return array_slice($evidence, 0, 50);
    }
}
