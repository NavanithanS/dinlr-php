# Error Handling

## Exception Hierarchy

| Exception | When thrown |
|---|---|
| `ValidationException` | Client-side validation failed (bad input) |
| `RateLimitException` | API rate limit exceeded |
| `ApiException` | Any other API error (base class) |
| `ConfigException` | Missing or invalid configuration |
| `WebhookException` | Webhook signature validation failed |

## Catching Exceptions

```php
use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Exception\ConfigException;
use Nava\Dinlr\Exception\RateLimitException;
use Nava\Dinlr\Exception\WebhookException;

try {
    $customer = $client->customers()->create($customerData);
    $order    = $client->cart()->submit($cartData);

} catch (ValidationException $e) {
    echo $e->getMessage() . "\n";
    foreach ($e->getErrors() as $field => $error) {
        echo "  {$field}: {$error}\n";
    }

} catch (RateLimitException $e) {
    $retryAfter = $e->getErrorData()['retry_after'] ?? 60;
    sleep($retryAfter);
    // then retry

} catch (ApiException $e) {
    echo 'HTTP ' . $e->getCode() . ': ' . $e->getMessage() . "\n";

    switch ($e->getCode()) {
        case 401: echo "Authentication failed — check API key\n"; break;
        case 403: echo "Forbidden — check permissions\n";         break;
        case 404: echo "Resource not found\n";                    break;
        case 422: echo "Unprocessable — check request data\n";    break;
        case 500: echo "Server error — retry later\n";            break;
    }

} catch (ConfigException $e) {
    echo 'Configuration error: ' . $e->getMessage() . "\n";
}
```

## Retry with Exponential Backoff

```php
function makeApiCallWithRetry(callable $callable, int $maxRetries = 3, int $baseDelay = 1)
{
    $attempt = 0;

    while ($attempt < $maxRetries) {
        try {
            return $callable();

        } catch (RateLimitException $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw $e;
            }
            sleep($baseDelay * pow(2, $attempt - 1));

        } catch (ApiException $e) {
            if (in_array($e->getCode(), [500, 502, 503, 504])) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                sleep($baseDelay);
            } else {
                throw $e; // Don't retry on client errors
            }
        }
    }
}

// Usage
$orders = makeApiCallWithRetry(fn() => $client->orders()->list());
```
