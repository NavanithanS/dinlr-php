# Dinlr PHP API Client

A comprehensive PHP client library for integrating with the Dinlr Online Order API. This library provides a clean, object-oriented interface to interact with all Dinlr API endpoints including orders, customers, inventory, reservations, loyalty programs, and more.

## Table of Contents

-   [Requirements](#requirements)
-   [Installation](#installation)
-   [Quick Start](#quick-start)
-   [Authentication](#authentication)
-   [Core Features](#core-features)
-   [What's New in v0.3.0](#whats-new-in-v030)
-   [Available Resources](#available-resources)
-   [Advanced Usage](docs/advanced-usage.md)
-   [Laravel Integration](docs/laravel.md)
-   [Webhook Handling](docs/webhooks.md)
-   [Error Handling](docs/error-handling.md)
-   [Testing](docs/testing.md)
-   [Contributing](docs/contributing.md)
-   [Security](docs/security.md)
-   [License](#license)
-   [Support](#support)

## Requirements

-   PHP 7.1 or higher
-   Guzzle HTTP library (^6.3|^7.0)
-   JSON extension

## Installation

Install via Composer:

```bash
composer require nava/dinlr-php
```

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use Nava\Dinlr\Client;

// Initialize the client
$client = new Client([
    'api_key' => 'your_api_key',
    'restaurant_id' => 'your_restaurant_id',
    'debug' => true,
]);

// Get restaurant details
$restaurant = $client->restaurant()->get();
echo "Restaurant: " . $restaurant->getName() . "\n";

// List locations
$locations = $client->locations()->list();
foreach ($locations as $location) {
    echo "Location: " . $location->getName() . "\n";
}

// Get menu items for a location
if (count($locations) > 0) {
    $locationId = $locations->first()->getId();
    $items = $client->items()->list($locationId);

    echo "Found " . count($items) . " menu items\n";
}
```

## Authentication

### API Key Authentication

The simplest way to authenticate with the Dinlr API:

```php
use Nava\Dinlr\Client;

$client = new Client([
    'api_key' => 'your_api_key',
    'restaurant_id' => 'your_restaurant_id',
    'api_url' => 'https://api.dinlr.com/v1', // optional
    'timeout' => 30, // optional, default: 30 seconds
    'debug' => true, // optional, default: false
]);
```

### OAuth 2.0 Authentication

For applications that need to access multiple restaurants or require user authorization:

```php
use Nava\Dinlr\OAuthClient;
use Nava\Dinlr\OAuthConfig;

// Step 1: Initialize OAuth client
$config = new OAuthConfig([
    'client_id' => 'your_client_id',
    'client_secret' => 'your_client_secret',
    'redirect_uri' => 'https://yourapp.com/oauth/callback',
    'api_url' => 'https://api.dinlr.com/v1',
]);

$client = new OAuthClient($config);

// Step 2: Redirect user to authorization URL
$state = bin2hex(random_bytes(16)); // Generate random state
session_start();
$_SESSION['oauth_state'] = $state;

$authUrl = $client->getAuthorizationUrl($state);
header("Location: {$authUrl}");
exit;
```

#### OAuth Callback Handler

```php
// callback.php
session_start();

try {
    // Step 3: Handle the callback
    $callbackData = $client->handleCallback($_GET, $_SESSION['oauth_state']);

    // Step 4: Exchange code for access token
    $tokens = $client->getAccessToken(
        $callbackData['code'],
        $callbackData['restaurant_id']
    );

    // Step 5: Store tokens and use the client
    $_SESSION['access_token'] = $tokens['access_token'];
    $_SESSION['refresh_token'] = $tokens['refresh_token'];
    $_SESSION['restaurant_id'] = $callbackData['restaurant_id'];

    // Now you can use the client with the access token
    $client->setAccessToken($tokens['access_token']);

    // Make API calls
    $restaurant = $client->restaurant()->get();
    echo "Connected to: " . $restaurant->getName();

} catch (\Nava\Dinlr\Exception\ApiException $e) {
    echo "OAuth error: " . $e->getMessage();
}
```

## Core Features

> Full examples for every resource — restaurant, menu, orders, loyalty, reservations, inventory and more:
> **[docs/core-features.md](docs/core-features.md)**

```php
// Restaurant & locations
$restaurant = $client->restaurant()->get();
$locations  = $client->locations()->list();
$locationId = $locations->first()->getId();

// Menu
$items    = $client->items()->list($locationId);
$menus    = $client->menu()->list($locationId);
$combos   = $client->comboGroups()->listForLocation($locationId);  // v0.3.0
$blocked  = $client->itemBlocks()->getItemBlocks($locationId);     // v0.3.0

// Customer
$customer = $client->customers()->create(['first_name' => 'Jane', 'email' => 'jane@example.com']);
$client->customers()->delete($customer->getId());  // v0.3.0

// Orders
$order = $client->cart()->submit($cartData);
$client->orders()->close($order->getId());

// Loyalty
$member = $client->loyalty()->enrolMember($programId, ['customer' => $customer->getId()]);
$client->loyalty()->addPoints($programId, $member->getId(), 100);
```

### Detailed examples by area

| Topic | Guide |
|---|---|
| Restaurant, menu, combos, item blocks | [docs/core-features.md](docs/core-features.md#menu-management) |
| Customers, search, QR | [docs/core-features.md](docs/core-features.md#customer-management) |
| Orders, cart, payments, KDS | [docs/core-features.md](docs/core-features.md#order-processing--cart) |
| Loyalty, members, transactions | [docs/core-features.md](docs/core-features.md#loyalty-programs) |
| Store credit | [docs/core-features.md](docs/core-features.md#store-credit) |
| Reservations | [docs/core-features.md](docs/core-features.md#reservations) |
| Discounts, promotions, vouchers | [docs/core-features.md](docs/core-features.md#discounts-promotions--vouchers) |
| Inventory & stock takes | [docs/core-features.md](docs/core-features.md#inventory) |

## What's New in v0.3.0

### Combo Groups

Retrieve combo groups defined for your menu, with optional filtering by location:

```php
// List all combo groups (optionally filter by location)
$comboGroups = $client->comboGroups()->list(null, ['location_id' => $locationId]);

foreach ($comboGroups as $comboGroup) {
    echo "Combo Group: " . $comboGroup->getName() . "\n";
    echo "  Required: " . ($comboGroup->isRequired() ? 'Yes' : 'No') . "\n";
    echo "  Min selection: " . ($comboGroup->getMinSelection() ?: 'None') . "\n";
    echo "  Max selection: " . ($comboGroup->getMaxSelection() ?: 'Unlimited') . "\n";

    $comboItems = $comboGroup->getComboGroupItems();
    echo "  Items (" . count($comboItems) . "):\n";
    foreach ($comboItems as $item) {
        echo "    - Item: " . $item['item'] . "\n";
    }
}

// Get a single combo group
$comboGroup = $client->comboGroups()->get($comboGroupId);

// Convenience: list combo groups for a specific location
$locationCombos = $client->comboGroups()->listForLocation($locationId);
```

### Item Blocks

Retrieve which items, item variants, and modifier options are currently blocked (unavailable) at a specific location:

```php
// Blocked menu items
$blockedItems = $client->itemBlocks()->getItemBlocks($locationId);
foreach ($blockedItems as $block) {
    if ($block->isBlocked()) {
        echo "Blocked item: " . $block->getItemId() . "\n";
    }
}

// Convenience: get just the IDs of currently blocked items
$blockedItemIds = $blockedItems->getBlockedItemIds();

// Blocked item variants
$blockedVariants = $client->itemBlocks()->getItemVariantBlocks($locationId);
$blockedVariantIds = $blockedVariants->getBlockedVariantIds();

// Blocked modifier options
$blockedOptions = $client->itemBlocks()->getModifierOptionBlocks($locationId);
$blockedOptionIds = $blockedOptions->getBlockedOptionIds();
```

### Customer: Delete & QR Code

```php
// Generate a time-sensitive QR code for in-restaurant customer identity verification
$qr = $client->customers()->generateQr($customerId);
echo "QR type: " . $qr['qr_type'] . "\n";
echo "Payload: " . $qr['payload'] . "\n";
echo "Expires at: " . $qr['expires_at'] . "\n";

// Delete a customer
$client->customers()->delete($customerId);
```

Customer search now also supports `external_reference`:

```php
$results = $client->customers()->search(['external_reference' => 'EXT-12345']);
```

### Loyalty: Update Member & Search Members

```php
// Update a member's expiry date
$client->loyalty()->updateMember($programId, $memberId, [
    'expires_at' => '2027-12-31T23:59:59Z',
]);

// Search members by customer ID
$members = $client->loyalty()->searchMembers($programId, [
    'customer_id' => $customerId,
]);

// Look up a customer's loyalty member record directly (uses search API with limit=1)
$member = $client->loyalty()->getMemberByCustomer($programId, $customerId);
if ($member !== null) {
    echo "Points balance: " . $member->getPoint() . "\n";
}
```

### Vouchers: Delete & Pools

```php
// Delete a voucher
$client->vouchers()->delete($voucherId);

// Retrieve all voucher pools for the restaurant
$pools = $client->vouchers()->getPools();
foreach ($pools as $pool) {
    echo "Pool: " . $pool['name'] . "\n";
}
```

---

## Available Resources

The Dinlr PHP client provides access to all API resources through dedicated classes:

### Core Resources

| Resource      | Description                              | Key Methods                              |
| ------------- | ---------------------------------------- | ---------------------------------------- |
| Restaurant    | Restaurant information                   | get()                                    |
| Location      | Restaurant locations                     | list(), get($locationId)                 |
| DiningOption  | Dining options (dine-in, takeaway, etc.) | list($locationId), get($diningOptionId)  |
| PaymentMethod | Available payment methods                | list($locationId), get($paymentMethodId) |
| Charge        | Additional charges                       | list($locationId), get($chargeId)        |

### Menu & Inventory

| Resource   | Description                           | Key Methods                                                                                                              |
| ---------- | ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| Item       | Menu items                            | list($locationId), get($itemId)                                                                                          |
| Category   | Item categories                       | list(), get($categoryId)                                                                                                 |
| Modifier   | Item modifiers                        | list($locationId), get($modifierId)                                                                                      |
| Menu       | Complete menu structure               | list($locationId)                                                                                                        |
| ComboGroup | Combo groups *(v0.3.0)*               | list(), get($id), listForLocation($locationId)                                                                           |
| ItemBlock  | Blocked items/variants/options *(v0.3.0)* | getItemBlocks($locationId), getItemVariantBlocks($locationId), getModifierOptionBlocks($locationId)                  |
| Material   | Inventory materials                   | list($locationId), getStockLevels($locationId)                                                                           |
| Floorplan  | Table and seating plans               | list($locationId), get($floorplanId)                                                                                     |

### Customer & Loyalty

| Resource      | Description             | Key Methods                                                                                                                                        |
| ------------- | ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Customer      | Customer management     | list(), get($id), create($data), update($id, $data), delete($id) *(v0.3.0)*, search($params), generateQr($id) *(v0.3.0)*                          |
| CustomerGroup | Customer segmentation   | list(), get($groupId)                                                                                                                              |
| Loyalty       | Loyalty programs        | getPrograms(), getRewards($programId), enrolMember(), updateMember() *(v0.3.0)*, searchMembers() *(v0.3.0)*, addPoints(), redeemReward()           |
| StoreCredit   | Store credit management | getCustomerBalance($customerId), addCredit($customerId, $amount), createTopup($data)                                                               |

### Orders & Transactions

| Resource  | Description                          | Key Methods                                                                                                          |
| --------- | ------------------------------------ | -------------------------------------------------------------------------------------------------------------------- |
| Cart      | Cart calculation and order placement | calculate($cartData), submit($cartData)                                                                              |
| Order     | Order management                     | list(), get($orderId), update($orderId, $data), addPayment($orderId, $data), close($orderId)                         |
| Discount  | Discount management                  | list($locationId), get($discountId)                                                                                  |
| Promotion | Promotional campaigns                | list($locationId), get($promotionId)                                                                                 |
| Voucher   | Voucher creation and management      | list(), get($id), create($data), update($id, $data), delete($id) *(v0.3.0)*, search($params), getPools() *(v0.3.0)* |

### Reservations

| Resource     | Description              | Key Methods                                                                                           |
| ------------ | ------------------------ | ----------------------------------------------------------------------------------------------------- |
| Experience   | Dining experiences       | list($locationId), get($experienceId)                                                                 |
| TableSection | Table section management | list($locationId), get($tableSectionId)                                                               |
| Reservation  | Reservation booking      | getAvailableServices($locationId, $date, $adult, $children), book($data), list(), get($reservationId) |

## Advanced Usage

Configuration options, per-request restaurant ID override, pagination, collection methods, and model helpers:
**[docs/advanced-usage.md](docs/advanced-usage.md)**

## Laravel Integration

Service provider setup, facades, `.env` variables, and OAuth controller:
**[docs/laravel.md](docs/laravel.md)**

## Webhook Handling

Signature validation, event types, and Laravel controller example:
**[docs/webhooks.md](docs/webhooks.md)**

## Error Handling

Exception types, status code handling, and retry with exponential backoff:
**[docs/error-handling.md](docs/error-handling.md)**

## Testing

Running tests, test configuration, writing custom tests, and static analysis:
**[docs/testing.md](docs/testing.md)**

## Contributing

Setup, coding standards, quality checks, and PR guidelines:
**[docs/contributing.md](docs/contributing.md)**

## Security

Vulnerability reporting, built-in security features, and best practices:
**[docs/security.md](docs/security.md)**

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

### Documentation and Resources

-   [Dinlr API Documentation](https://docs.dinlr.com) - Complete API reference
-   [Support Portal](https://support.dinlr.com) - Official support and help center
-   [GitHub Issues](https://github.com/dinlr/dinlr-php/issues) - Report bugs and request features
-   [Upgrade Guide v0.3.0](docs/upgrade-v0.3.0.md) - What's new and migration notes
-   [Changelog](CHANGELOG.md) - Full release history

### Getting Help

For library-specific issues:

-   Check the GitHub Issues page
-   Search existing issues before creating new ones
-   Provide detailed information when reporting bugs

For API and business questions:

-   Visit https://support.dinlr.com
-   Contact the Dinlr support team directly

### Community

-   GitHub Discussions: Share ideas and get help from the community
