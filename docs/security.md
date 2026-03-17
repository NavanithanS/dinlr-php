# Security

## Reporting Vulnerabilities

Do not report security vulnerabilities through public GitHub issues.

Email **security@dinlr.com** with:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

We respond to security reports within 24 hours.

## Built-in Security Features

| Feature | Description |
|---|---|
| Input sanitization | All user inputs are sanitized via `InputSanitizer` before use |
| Input validation | Strings, emails, dates, and IDs are validated in `AbstractResource` |
| Rate limit handling | `RateLimitException` lets callers implement backoff strategies |
| Webhook signatures | Cryptographic HMAC verification via `WebhookValidator` (timing-safe) |
| Log sanitization | Sensitive fields (tokens, keys, passwords) are redacted from debug logs |
| Path traversal protection | `buildPath()` guards against malformed restaurant/resource IDs |

## Best Practices

- **Never commit API keys** — use environment variables or a secrets manager
- **Always validate webhook signatures** before processing webhook data
- **Don't expose error details** to end users — log them server-side
- **Use HTTPS** — the library enforces HTTPS connections to the API
- **Keep the library updated** — security fixes are released as patch versions
