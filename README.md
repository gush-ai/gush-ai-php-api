# Gush AI PHP API Client & Secure Proxy

A production-oriented PHP integration for the Gush AI Developer API.

It provides:

- A reusable PHP `GushClient`
- A secure server-side `/chat` endpoint
- Bearer-token authentication to Gush AI
- JSON request/response handling
- Input validation
- Configurable model, temperature, and max tokens
- Optional CORS allow-list
- Basic file-backed rate limiting
- No API-token exposure to browser clients
- No message-content logging by default

## Gush AI endpoint

The client sends requests to:

`https://ai.sstore.ng/api-access`

Required header:

```http
Content-Type: application/json
Authorization: Bearer YOUR_GUSH_API_TOKEN
```

Example payload:

```json
{
  "messages": [
    {
      "role": "user",
      "content": "How do you refactor concurrent routines in PHP?"
    }
  ],
  "model": "gemini::gemini-2.5-flash",
  "temperature": 0.7,
  "max_tokens": 4096
}
```

## Requirements

- PHP 8.1+
- PHP cURL extension
- PHP JSON extension
- HTTPS in production

No Composer dependency is required.

## Deployment

1. Upload the repository to your PHP server.
2. Point the web server document root to `public/`.
3. Set the environment variable:

```bash
GUSH_API_TOKEN=your_real_token
```

Optional:

```bash
GUSH_API_URL=https://ai.sstore.ng/api-access
GUSH_DEFAULT_MODEL=gemini::gemini-2.5-flash
GUSH_MAX_INPUT_CHARS=50000
GUSH_MAX_OUTPUT_TOKENS=4096
GUSH_RATE_LIMIT=60
GUSH_RATE_WINDOW=60
GUSH_ALLOWED_ORIGINS=https://your-app.example
```

4. Ensure the `storage/` directory is writable if using the built-in file rate limiter. The application creates it automatically.
5. Test:

```bash
curl -X POST https://your-domain.example/chat \
  -H "Content-Type: application/json" \
  -d '{
    "messages":[
      {"role":"user","content":"Hello Gush AI"}
    ]
  }'
```

The Gush API token stays on the server and is never returned to the client.

## Direct PHP usage

```php
require __DIR__ . '/src/GushClient.php';

$client = new GushAI\GushClient(
    getenv('GUSH_API_TOKEN'),
    getenv('GUSH_API_URL') ?: 'https://ai.sstore.ng/api-access'
);

$response = $client->chat([
    ['role' => 'user', 'content' => 'Hello Gush AI']
], [
    'model' => 'gemini::gemini-2.5-flash',
    'temperature' => 0.7,
    'max_tokens' => 4096,
]);

print_r($response);
```

## Security model

The public client should call your PHP `/chat` endpoint, not `https://ai.sstore.ng/api-access` directly.

```text
Browser / Mobile App
        |
        | POST /chat
        v
Your PHP API
        |
        | Authorization: Bearer <server-side token>
        v
Gush AI API
        |
        v
Gush response
```

Never put `GUSH_API_TOKEN` in JavaScript, HTML, mobile source code, GitHub, or public configuration.

## Important authorization note

This repository intentionally does not trust client-supplied project IDs, integration IDs, tool IDs, or privileged account identifiers. If your application adds those fields later, authorization must be performed server-side against the authenticated user before forwarding anything to Gush AI.

## Response behavior

The proxy returns the upstream Gush response with an appropriate HTTP status. Upstream error bodies are returned as JSON where possible, without exposing the server-side Gush token.

## Production recommendations

For a larger deployment, replace the simple file rate limiter with Redis or your existing API gateway, add authenticated application users, configure strict CORS, add request IDs, and put the endpoint behind HTTPS and a reverse proxy/WAF.

## License

MIT. See `LICENSE`.
