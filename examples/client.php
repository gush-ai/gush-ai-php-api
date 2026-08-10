<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/GushClient.php';

use GushAI\GushClient;

$client = new GushClient(
    getenv('GUSH_API_TOKEN') ?: '',
    getenv('GUSH_API_URL') ?: 'https://ai.sstore.ng/api-access'
);

$result = $client->chat(
    [
        ['role' => 'user', 'content' => 'How do you refactor concurrent routines in PHP?']
    ],
    [
        'model' => 'gemini::gemini-2.5-flash',
        'temperature' => 0.7,
        'max_tokens' => 4096,
    ]
);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
