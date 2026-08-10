<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

use GushAI\GushClient;

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', env_value('GUSH_ALLOWED_ORIGINS', '') ?? '')
)));

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
}

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method === 'GET' && $path === '/') {
    json_response([
        'service' => 'Gush AI PHP API',
        'status' => 'ok',
        'endpoint' => '/chat',
        'method' => 'POST',
    ]);
}

if ($method !== 'POST' || $path !== '/chat') {
    json_response(['error' => 'Route not found.'], 404);
}

$rateLimit = max(1, (int) env_value('GUSH_RATE_LIMIT', '60'));
$rateWindow = max(1, (int) env_value('GUSH_RATE_WINDOW', '60'));
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$bucket = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $ip) ?: 'unknown';

$storageDir = dirname(__DIR__) . '/storage';
if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0750, true);
}

$rateFile = $storageDir . '/rate_' . hash('sha256', $bucket) . '.json';
$now = time();
$state = ['start' => $now, 'count' => 0];

if (is_file($rateFile)) {
    $saved = json_decode((string) @file_get_contents($rateFile), true);
    if (is_array($saved)) {
        $state = array_merge($state, $saved);
    }
}

if (($now - (int) $state['start']) >= $rateWindow) {
    $state = ['start' => $now, 'count' => 0];
}

$state['count']++;
@file_put_contents($rateFile, json_encode($state), LOCK_EX);

if ($state['count'] > $rateLimit) {
    header('Retry-After: ' . max(1, $rateWindow - ($now - (int) $state['start'])));
    json_response(['error' => 'Rate limit exceeded.'], 429);
}

$body = request_json();

$messages = validate_messages(
    $body['messages'] ?? null,
    max(1000, (int) env_value('GUSH_MAX_INPUT_CHARS', '50000'))
);

$model = $body['model'] ?? env_value('GUSH_DEFAULT_MODEL', 'gemini::gemini-2.5-flash');
if (!is_string($model) || !preg_match('/^[a-zA-Z0-9_.:-]{1,120}$/', $model)) {
    json_response(['error' => 'Invalid model value.'], 422);
}

$temperature = $body['temperature'] ?? 0.7;
if (!is_numeric($temperature) || (float) $temperature < 0 || (float) $temperature > 2) {
    json_response(['error' => 'temperature must be between 0 and 2.'], 422);
}

$maxTokens = $body['max_tokens'] ?? 4096;
$maxAllowed = max(1, (int) env_value('GUSH_MAX_OUTPUT_TOKENS', '4096'));

if (!is_int($maxTokens) && !ctype_digit((string) $maxTokens)) {
    json_response(['error' => 'max_tokens must be an integer.'], 422);
}

$maxTokens = (int) $maxTokens;
if ($maxTokens < 1 || $maxTokens > $maxAllowed) {
    json_response([
        'error' => 'max_tokens exceeds the configured server limit.',
        'max_allowed' => $maxAllowed,
    ], 422);
}

try {
    $client = new GushClient(
        (string) env_value('GUSH_API_TOKEN', ''),
        (string) env_value('GUSH_API_URL', 'https://ai.sstore.ng/api-access')
    );

    $response = $client->chat($messages, [
        'model' => $model,
        'temperature' => (float) $temperature,
        'max_tokens' => $maxTokens,
    ]);

    json_response($response, 200);
} catch (Throwable $e) {
    error_log('Gush API proxy error: ' . $e->getMessage());
    json_response([
        'error' => 'Gush AI request failed.',
        'message' => 'The upstream AI service could not complete the request.'
    ], 502);
}
