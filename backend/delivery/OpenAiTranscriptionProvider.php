<?php
declare(strict_types=1);

namespace YuvaClub\Delivery;

final class OpenAiTranscriptionProvider implements MediaTranscriptionProvider
{
    public function __construct(private readonly string $apiKey, private readonly string $model = 'whisper-1', private readonly int $timeoutSeconds = 90) {}
    public function providerName(): string { return 'openai'; }
    public function modelName(): string { return $this->model; }

    public function transcribe(string $path, string $originalName, string $mimeType): array
    {
        if ($this->apiKey === '' || !is_file($path) || !is_readable($path)) return ['ok' => false, 'error_code' => 'transcription_failed'];
        $curl = curl_init('https://api.openai.com/v1/audio/transcriptions');
        if ($curl === false) return ['ok' => false, 'error_code' => 'transcription_failed'];
        $file = new \CURLFile($path, $mimeType, basename($originalName));
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_POSTFIELDS => [
                'file' => $file,
                'model' => $this->model,
                'response_format' => 'verbose_json',
            ] + $this->timestampGranularityFields(),
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $failed = $raw === false || curl_errno($curl) !== 0;
        curl_close($curl);
        if ($failed || $status < 200 || $status >= 300) return ['ok' => false, 'error_code' => $status === 408 ? 'provider_timeout' : 'transcription_failed'];
        $data = json_decode((string) $raw, true);
        if (!is_array($data) || trim((string) ($data['text'] ?? '')) === '') return ['ok' => false, 'error_code' => 'no_speech_detected'];
        $timing=$this->normalizeTiming($data);
        return ['ok' => true, 'transcript' => new PresentationTranscript(
            trim((string) $data['text']),
            (float) ($data['duration'] ?? 0),
            $timing['segments'],
            $timing['words'],
            (string) ($data['language'] ?? ''),
            ['provider' => 'openai', 'model' => $this->model, 'segments_derived_from_words'=>$timing['segments_derived_from_words']]
        )];
    }

    /** @return array<string,string> */
    private function timestampGranularityFields(): array
    {
        return ['timestamp_granularities[]' => 'word'];
    }

    /** @return array{segments:array<int,array<string,mixed>>,words:array<int,array<string,mixed>>,segments_derived_from_words:bool} */
    private function normalizeTiming(array $data): array
    {
        $words=is_array($data['words']??null)?$data['words']:[];
        $segments=is_array($data['segments']??null)?$data['segments']:[];
        $derived=$segments===[]&&$words!==[];
        if($derived)$segments=array_map(static fn(array $word):array=>['start'=>(float)($word['start']??0),'end'=>(float)($word['end']??$word['start']??0),'text'=>(string)($word['word']??'')],$words);
        return ['segments'=>$segments,'words'=>$words,'segments_derived_from_words'=>$derived];
    }
}
