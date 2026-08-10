# Deployment

## Apache

Point the virtual host document root to:

```text
/path/to/gush-ai-php-api/public
```

A basic `public/.htaccess` can route `/chat` to `index.php`.

## PHP built-in server

For testing only:

```bash
php -S 127.0.0.1:8080 -t public
```

Then:

```bash
curl -X POST http://127.0.0.1:8080/chat \
  -H 'Content-Type: application/json' \
  -d '{"messages":[{"role":"user","content":"Hello Gush AI"}]}'
```

## Environment

Configure:

```text
GUSH_API_TOKEN=...
GUSH_API_URL=https://ai.sstore.ng/api-access
```

Optional limits are documented in `.env.example`.

## Production

Use:

- PHP 8.1+
- HTTPS
- process-level environment secrets
- a real rate limiter for multi-server deployments
- application authentication
- monitoring without sensitive payload logging
