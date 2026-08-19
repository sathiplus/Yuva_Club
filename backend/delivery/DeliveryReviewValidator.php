<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class DeliveryReviewValidator
{
    private const SCORES = ['overall_delivery_score','pace_score','pause_control_score','clarity_score','vocal_variety_score','emphasis_score','filler_word_score'];

    /** @return array{ok:bool,review?:array<string,mixed>,error?:string} */
    public function validate(array $input): array
    {
        foreach (self::SCORES as $field) {
            if (!isset($input[$field]) || !is_numeric($input[$field]) || (int)$input[$field] < 0 || (int)$input[$field] > 100) return $this->fail('invalid_score');
            $input[$field] = (int)$input[$field];
        }
        if (array_key_exists('visual_presence_score', $input) && $input['visual_presence_score'] !== null) {
            if (!is_numeric($input['visual_presence_score']) || (int)$input['visual_presence_score'] < 0 || (int)$input['visual_presence_score'] > 100) return $this->fail('invalid_visual_score');
            $input['visual_presence_score'] = (int)$input['visual_presence_score'];
        }
        foreach (['summary','pacing_feedback','pause_feedback','clarity_feedback','filler_word_feedback','visual_feedback','recommended_next_step','admin_notes'] as $field) {
            if (!isset($input[$field]) || !is_string($input[$field]) || trim($input[$field]) === '' || mb_strlen($input[$field]) > 2400) return $this->fail('invalid_feedback');
            $input[$field] = trim($input[$field]);
        }
        foreach (['strengths','improvements','pronunciation_practice','emphasis_opportunities','timecoded_coaching'] as $field) if (!isset($input[$field]) || !is_array($input[$field]) || count($input[$field]) > 20) return $this->fail('invalid_items');
        if (!isset($input['suggested_tokens']) || !is_numeric($input['suggested_tokens']) || (int)$input['suggested_tokens'] < 0 || (int)$input['suggested_tokens'] > 4) return $this->fail('invalid_tokens');
        $input['suggested_tokens'] = (int)$input['suggested_tokens'];
        foreach ($input['timecoded_coaching'] as $item) {
            if (!is_array($item) || !is_numeric($item['start_seconds'] ?? null) || (float)$item['start_seconds'] < 0 || !in_array($item['category'] ?? '', ['pace','pause','filler','clarity','pronunciation','emphasis','visual','structure'], true)) return $this->fail('invalid_timecode');
            if (isset($item['end_seconds']) && (!is_numeric($item['end_seconds']) || (float)$item['end_seconds'] < (float)$item['start_seconds'])) return $this->fail('invalid_timecode');
            foreach (['observation','recommendation','priority'] as $field) if (!isset($item[$field]) || !is_string($item[$field]) || trim($item[$field]) === '' || mb_strlen($item[$field]) > 600) return $this->fail('invalid_timecode');
        }
        $allowed=array_merge(self::SCORES,['visual_presence_score','summary','strengths','improvements','pacing_feedback','pause_feedback','clarity_feedback','pronunciation_practice','emphasis_opportunities','filler_word_feedback','visual_feedback','timecoded_coaching','recommended_next_step','suggested_tokens','admin_notes']);
        if(array_diff(array_keys($input),$allowed)!==[])return $this->fail('unexpected_fields');
        return ['ok' => true, 'review' => $input];
    }
    private function fail(string $code): array { return ['ok' => false, 'error' => $code]; }
}
