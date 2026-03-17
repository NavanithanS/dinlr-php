# Webhook Handling

The library includes cryptographic webhook signature validation.

## Basic Usage

```php
use Nava\Dinlr\Webhook\WebhookValidator;
use Nava\Dinlr\Exception\WebhookException;

$validator = new WebhookValidator($webhookSecret);

try {
    $event = $validator->constructEvent(
        $request->getContent(),
        $request->header('Dinlr-Signature')
    );

    if ($event->isOrderEvent()) {
        $this->handleOrderEvent($event);
    } elseif ($event->isCustomerEvent()) {
        $this->handleCustomerEvent($event);
    }

} catch (WebhookException $e) {
    // Invalid or missing signature
    http_response_code(400);
    exit;
}
```

## Laravel Controller Example

```php
use Nava\Dinlr\Webhook\WebhookValidator;
use Nava\Dinlr\Exception\WebhookException;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $validator = new WebhookValidator(config('dinlr.webhook_secret'));

        try {
            $event = $validator->constructEvent(
                $request->getContent(),
                $request->header('Dinlr-Signature')
            );

            if ($event->isOrderEvent()) {
                $orderData = $event->getData();

                if ($event->isCreateEvent()) {
                    event(new OrderCreated($orderData));
                } elseif ($event->isUpdateEvent()) {
                    event(new OrderUpdated($orderData));
                }

            } elseif ($event->isCustomerEvent()) {
                if ($event->isCreateEvent()) {
                    event(new CustomerRegistered($event->getData()));
                }
            }

            return response('OK', 200);

        } catch (WebhookException $e) {
            Log::warning('Invalid webhook', [
                'error'     => $e->getMessage(),
                'signature' => $request->header('Dinlr-Signature'),
            ]);

            return response('Invalid webhook', 400);
        }
    }
}
```

## Event Methods

| Method | Description |
|---|---|
| `$event->isOrderEvent()` | True for order events |
| `$event->isCustomerEvent()` | True for customer events |
| `$event->isCreateEvent()` | True for create webhooks |
| `$event->isUpdateEvent()` | True for update webhooks |
| `$event->getData()` | Returns the event payload array |
