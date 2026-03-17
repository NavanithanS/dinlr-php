# Advanced Usage

## Custom Configuration

```php
use Nava\Dinlr\Config;
use Nava\Dinlr\Client;

$config = new Config([
    'api_key'       => 'your_key',
    'restaurant_id' => 'your_id',
    'api_url'       => 'https://api.dinlr.com/v1',
    'timeout'       => 60,
    'debug'         => true,
]);

$client = new Client($config);
```

## Configuration Reference

| Option | Type | Description | Default | Required |
|---|---|---|---|---|
| `api_key` | string | Your Dinlr API key | — | Yes |
| `restaurant_id` | string | Your restaurant ID | — | Yes |
| `api_url` | string | Base URL for Dinlr API | `https://api.dinlr.com/v1` | No |
| `timeout` | int | Request timeout in seconds | `30` | No |
| `debug` | bool | Enable debug logging | `false` | No |

## OAuth Configuration Reference

| Option | Type | Description | Required |
|---|---|---|---|
| `client_id` | string | OAuth client ID | Yes |
| `client_secret` | string | OAuth client secret | Yes |
| `redirect_uri` | string | OAuth redirect URI | Yes |
| `access_token` | string | Current access token | No |
| `refresh_token` | string | Current refresh token | No |
| `expires_at` | int | Token expiration timestamp | No |

## Per-Request Restaurant ID Override

All resource methods accept an optional `$restaurantId` as their last parameter, overriding the default set in config:

```php
$orders    = $client->orders()->list('other_restaurant_id');
$customer  = $client->customers()->get($customerId, 'other_restaurant_id');
```

## Pagination and Filtering

```php
// Paginated list
$customers = $client->customers()->list(null, [
    'limit' => 50,
    'page'  => 2,
]);

// Date filtering
$orders = $client->orders()->list(null, [
    'created_at_min' => '2024-01-01T00:00:00Z',
    'created_at_max' => '2024-12-31T23:59:59Z',
    'status'         => 'open',
    'detail'         => 'all',
]);

// Location-scoped convenience methods
$items  = $client->items()->list($locationId);
$orders = $client->orders()->listForLocation($locationId);
```

## Collection Methods

All list operations return typed collections with shared helper methods:

```php
// Navigation
$first = $collection->first();
$last  = $collection->last();
$count = count($collection);

// Custom filter
$vip = $customers->filter(fn($c) => $c->hasCompleteProfile());

// Order collection
$openOrders   = $orders->getByStatus('open');
$paidOrders   = $orders->getByFinancialStatus('paid');
$totalRevenue = $orders->getTotalRevenue();

// Reservation collection
$upcoming  = $reservations->getUpcoming();
$totalPax  = $reservations->getTotalPax();

// Loyalty member collection
$totalPoints = $members->getTotalPoints();

// Item block collections (v0.3.0)
$blockedItemIds    = $itemBlocks->getBlockedItemIds();
$blockedVariantIds = $variantBlocks->getBlockedVariantIds();
$blockedOptionIds  = $optionBlocks->getBlockedOptionIds();
```

## Model Helper Methods

Models expose business logic beyond basic getters:

```php
// Customer
$customer->hasCompleteProfile();
$customer->canReceiveMarketing('email'); // 'email' | 'text' | 'phone'
$customer->getAge();
$customer->isInAgeRange(18, 65);

// Order
$order->isPaid();
$order->isPartiallyPaid();
$order->isOpen();
$order->isClosed();
$order->getItemCount();

// Voucher
$voucher->canBeRedeemed();
$voucher->isExpired();

// Loyalty member
$member->hasSufficientPoints($reward->getPoint());

// Store credit balance
$balance->hasStoreCredit();
$balance->hasSufficientCredit(25.00);

// Combo group (v0.3.0)
$comboGroup->isRequired(); // min_selection > 0

// Item block (v0.3.0)
$block->isBlocked(); // status === 'blocked'
```
