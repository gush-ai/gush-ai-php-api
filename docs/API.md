# API Documentation

## POST `/chat`

Your PHP application accepts a request and securely forwards it to the Gush AI Developer API.

### Request

```http
POST /chat
Content-Type: application/json
```

Body:

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

### Fields

| Field | Type | Required | Notes |
|---|---|---:|---|
| messages | array | Yes | 1–100 messages |
| messages[].role | string | Yes | `system`, `user`, or `assistant` |
| messages[].content | string | Yes | Non-empty text |
| model | string | No | Defaults to `gemini::gemini-2.5-flash` |
| temperature | number | No | 0–2 |
| max_tokens | integer | No | Server-capped |

### Security

The Gush API token is stored server-side as `GUSH_API_TOKEN`.

The browser/mobile client must never receive this token.

### Errors

- `400` Invalid/missing JSON
- `404` Route not found
- `413` Input too large
- `415` Invalid content type
- `422` Validation failure
- `429` Rate limit exceeded
- `502` Upstream Gush API failure
