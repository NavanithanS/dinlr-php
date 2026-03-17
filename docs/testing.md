# Testing

## Running Tests

```bash
# Full suite
composer test

# Specific file
./vendor/bin/phpunit tests/CustomerApiTest.php
./vendor/bin/phpunit tests/LoyaltyTest.php
./vendor/bin/phpunit tests/ComboGroupTest.php
./vendor/bin/phpunit tests/ItemBlockTest.php

# Verbose output
./vendor/bin/phpunit --verbose

# Coverage report
./vendor/bin/phpunit --coverage-html coverage/
```

## Test Configuration

Tests use real API credentials. Copy the config template:

```bash
cp tests/config.php tests/config.local.php
```

Or set environment variables:

```bash
export DINLR_TEST_API_KEY="your_test_api_key"
export DINLR_TEST_RESTAURANT_ID="your_test_restaurant_id"
export DINLR_TEST_API_URL="https://api.dinlr.com/v1"
export DINLR_TEST_DEBUG="true"
```

`tests/config.php` reads these with fallbacks:

```php
<?php
return [
    'api_key'       => getenv('DINLR_TEST_API_KEY') ?: 'your_test_api_key',
    'api_url'       => getenv('DINLR_TEST_API_URL') ?: 'https://api.dinlr.com/v1',
    'restaurant_id' => getenv('DINLR_TEST_RESTAURANT_ID') ?: 'your_test_restaurant_id',
    'timeout'       => (int) (getenv('DINLR_TEST_TIMEOUT') ?: 30),
    'debug'         => (bool) (getenv('DINLR_TEST_DEBUG') ?: true),
];
```

## Writing Tests

```php
<?php
use Nava\Dinlr\Client;
use PHPUnit\Framework\TestCase;

class MyDinlrTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $config       = require __DIR__ . '/config.php';
        $this->client = new Client($config);
    }

    public function testCustomerCreation(): void
    {
        $customer = $this->client->customers()->create([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'email'      => 'test@example.com',
        ]);

        $this->assertInstanceOf(\Nava\Dinlr\Models\Customer::class, $customer);
        $this->assertEquals('Test Customer', $customer->getFullName());

        // Cleanup
        $this->client->customers()->delete($customer->getId());
    }
}
```

## Static Analysis

```bash
composer analyse

# Or directly with level
./vendor/bin/phpstan analyse src tests --level=7
```

## Code Style

```bash
# Check
composer cs

# Fix
composer cs-fix
```
