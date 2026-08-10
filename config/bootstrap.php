<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/GushClient.php';

function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

function request_json(): array
{
    $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
    if (!str_contains($contentType, 'application/json')) {
        json_response(['error' => 'Content-Type must be application/json.'], 415);
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        json_response(['error' => 'Request body is required.'], 400);
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_response(['error' => 'Invalid JSON body.'], 400);
    }

    return $data;
}

function validate_messages(mixed $messages, int $maxInputChars): array
{
    if (!is_array($messages) || count($messages) < 1 || count($messages) > 100) {
        json_response(['error' => 'messages must contain between 1 and 100 items.'], 422);
    }

    $allowedRoles = ['system', 'user', 'assistant'];
    $totalChars = 0;
    $validated = [];

    foreach ($messages as $index => $message) {
        if (!is_array($message)) {
            json_response(['error' => "messages[$index] must be an object."], 422);
        }

        $role = $message['role'] ?? null;
        $content = $message['content'] ?? null;

        if (!is_string($role) || !in_array($role, $allowedRoles, true)) {
            json_response(['error' => "messages[$index].role is invalid."], 422);
        }

        if (!is_string($content) || trim($content) === '') {
            json_response(['error' => "messages[$index].content is required."], 422);
        }

        $totalChars += strlen($content);
        if ($totalChars > $maxInputChars) {
            json_response(['error' => 'Input exceeds the configured size limit.'], 413);
        }

        $validated[] = [
            'role' => $role,
            'content' => $content,
        ];
    }

    return $validated;
}
