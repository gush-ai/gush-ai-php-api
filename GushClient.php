<?php
declare(strict_types=1);

namespace GushAI;

use RuntimeException;

final class GushClient
{
    public function __construct(
        private readonly string $apiToken,
        private readonly string $endpoint = 'https://ai.sstore.ng/api-access',
        private readonly int $timeoutSeconds = 60,
    ) {
        if ($this->apiToken === '') {
            throw new RuntimeException('GUSH_API_TOKEN is not configured.');
        }
    }

    /**
     * @param array<int, array{role:string, content:string}> $messages
     * @param array{model?:string, temperature?:float|int, max_tokens?:int} $options
     * @return array<string,mixed>
     */
    public function chat(array $messages, array $options = []): array
    {
        $payload = [
            'messages' => $messages,
            'model' => $options['model'] ?? 'gemini::gemini-2.5-flash',
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 4096,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Unable to encode request JSON.');
        }

        $ch = curl_init($this->endpoint);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiToken,
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('Gush API request failed: ' . $curlError);
        }

        $decoded = json_decode($body, true);

        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded)
                ? ($decoded['error'] ?? $decoded['message'] ?? 'Gush API returned an error.')
                : 'Gush API returned HTTP ' . $status . '.';

            throw new RuntimeException((string) $message, $status ?: 502);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Gush API returned an invalid JSON response.');
        }

        return $decoded;
    }
}
