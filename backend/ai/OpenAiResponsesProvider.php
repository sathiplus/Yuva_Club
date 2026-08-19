<?php
declare(strict_types=1);

namespace YuvaClub\AI;

final class OpenAiResponsesProvider implements AiProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds = 45
    ) {
    }

    public function providerName(): string
    {
        return 'openai';
    }

    public function modelName(): string
    {
        return $this->model;
    }

    public function generateStructuredReview(string $prompt): array
    {
        if ($this->apiKey === '') {
            return [
                'ok' => false,
                'error' => 'OPENAI_API_KEY is not configured on the server.',
            ];
        }

        $payload = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'system',
                    'content' => 'You are a child-safe educational coach. Return only valid JSON.',
                ],
                ['role' => 'user', 'content' => $prompt],
            ],
            'text' => ['format' => ['type' => 'json_object']],
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        if ($ch === false) {
            return ['ok' => false, 'error' => 'Could not initialize cURL.'];
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            return ['ok' => false, 'error' => 'OpenAI request failed: ' . $curlError];
        }

        $response = json_decode((string) $raw, true);
        if (!is_array($response)) {
            return ['ok' => false, 'error' => 'OpenAI returned an unreadable response.'];
        }
        if ($status < 200 || $status >= 300) {
            return [
                'ok' => false,
                'error' => (string) (
                    $response['error']['message']
                    ?? ('OpenAI returned HTTP ' . $status)
                ),
            ];
        }

        $text = $this->responseText($response);
        $output = json_decode($text, true);
        if (!is_array($output)) {
            return ['ok' => false, 'error' => 'AI response was not valid JSON.'];
        }
        return ['ok' => true, 'output' => $output];
    }

    /** @param array<string, mixed> $response */
    private function responseText(array $response): string
    {
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }
        $parts = [];
        foreach (($response['output'] ?? []) as $output) {
            if (!is_array($output)) {
                continue;
            }
            foreach (($output['content'] ?? []) as $content) {
                if (
                    is_array($content)
                    && ($content['type'] ?? '') === 'output_text'
                    && isset($content['text'])
                ) {
                    $parts[] = (string) $content['text'];
                }
            }
        }
        return trim(implode("\n", $parts));
    }
}
