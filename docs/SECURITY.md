# Security Notes

## Token handling

Keep `GUSH_API_TOKEN` outside the Git repository.

Preferred options:

- Server environment variable
- Secret manager
- Hosting control-panel secret configuration

Never commit a real token to GitHub.

## Authorization

The proxy validates the request shape, but application-level authentication is intentionally left to the deploying project.

If the endpoint is public, anyone who can reach `/chat` may consume your Gush API quota. Put your own authentication/session layer in front of `/chat` for production applications.

## Tool and integration IDs

Do not add client-controlled `tool_id`, `integration_id`, `project_id`, account IDs, or privileged identifiers and blindly forward them upstream. Verify ownership and permissions on the server first.

## Logs

Do not log:

- Gush API tokens
- Authorization headers
- Full user prompts
- Full AI responses when they may contain private data

The example endpoint logs only a generic upstream error.

## CORS

Leave `GUSH_ALLOWED_ORIGINS` empty unless browser cross-origin access is required. If enabled, list exact trusted origins.

## Rate limiting

The included limiter is a lightweight deployment default. For multiple PHP workers/servers, use Redis, a reverse proxy, or a dedicated API gateway instead of local files.

## HTTPS

Use HTTPS in production. Never send bearer credentials over plaintext HTTP.
