# Core Features

Detailed usage examples for every resource in the Dinlr PHP client.

## Restaurant & Settings

```php
$restaurant = $client->restaurant()->get();
echo $restaurant->getName() . ' (' . $restaurant->getCurrency() . ')';

$locations     = $client->locations()->list();
$locationId    = $locations->first()->getId();
$diningOptions = $client->diningOptions()->list($locationId);
$paymentMethods = $client->paymentMethods()->list($locationId);

$charges = $client->charges()->list($locationId);
foreach ($charges as $charge) {
    echo $charge->getName() . ' — applies to ' . count($charge->getDiningOptions()) . " dining options\n";
}
```

## Menu Management

```php
// Items with variants and modifiers
$items = $client->items()->list($locationId);
foreach ($items as $item) {
    echo $item->getName() . "\n";
    foreach ($item->getVariants() as $variant) {
        $price = $variant['price'] ? '$' . $variant['price'] : 'Open price';
        echo '  - ' . $variant['name'] . ': ' . $price . "\n";
    }
}

// Hierarchical categories
$categories = $client->categories()->list();
foreach ($categories as $category) {
    if ($category->isTopLevel()) {
        echo $category->getName() . "\n";
    }
}

// Modifiers
$modifiers = $client->modifiers()->list($locationId);
foreach ($modifiers as $modifier) {
    echo $modifier->getName() . ' (required: ' . ($modifier->isRequired() ? 'yes' : 'no') . ")\n";
    foreach ($modifier->getModifierOptions() as $option) {
        $price = $option['price'] > 0 ? ' (+$' . $option['price'] . ')' : ' (Free)';
        echo '  - ' . $option['name'] . $price . "\n";
    }
}

// Menu with time-based availability
$menus = $client->menu()->list($locationId);
foreach ($menus as $menu) {
    echo $menu->getName() . ' — available today: ' . ($menu->isAvailableOnDay(date('l')) ? 'yes' : 'no') . "\n";
}
```

## Combo Groups *(v0.3.0)*

```php
$comboGroups = $client->comboGroups()->list(null, ['location_id' => $locationId]);
foreach ($comboGroups as $group) {
    echo $group->getName() . ' (required: ' . ($group->isRequired() ? 'yes' : 'no') . ")\n";
    foreach ($group->getComboGroupItems() as $item) {
        echo '  - item: ' . $item['item'] . "\n";
    }
}

$group  = $client->comboGroups()->get($comboGroupId);
$groups = $client->comboGroups()->listForLocation($locationId);
```

## Item Blocks *(v0.3.0)*

```php
// Blocked items at a location
$blockedItems    = $client->itemBlocks()->getItemBlocks($locationId);
$blockedItemIds  = $blockedItems->getBlockedItemIds(); // array of IDs with status 'blocked'

// Blocked variants
$blockedVariants    = $client->itemBlocks()->getItemVariantBlocks($locationId);
$blockedVariantIds  = $blockedVariants->getBlockedVariantIds();

// Blocked modifier options
$blockedOptions    = $client->itemBlocks()->getModifierOptionBlocks($locationId);
$blockedOptionIds  = $blockedOptions->getBlockedOptionIds();

// Supports pagination
$blocks = $client->itemBlocks()->getItemBlocks($locationId, null, [
    'limit'          => 50,
    'updated_at_min' => '2026-01-01T00:00:00Z',
]);
```

## Customer Management

```php
// Create
$customer = $client->customers()->create([
    'first_name'              => 'John',
    'last_name'               => 'Doe',
    'email'                   => 'john@example.com',
    'phone'                   => '+1234567890',
    'dob'                     => '1990-01-15',
    'gender'                  => 'M',
    'country'                 => 'US',
    'marketing_consent_email' => true,
]);

// Read / update / delete
$customer = $client->customers()->get($customerId);
$client->customers()->update($customerId, ['notes' => 'VIP']);
$client->customers()->delete($customerId);

// Search — reference, email, phone, external_reference
$results = $client->customers()->search(['email' => 'john@example.com']);
$results = $client->customers()->search(['external_reference' => 'EXT-001']);

// QR code for in-restaurant identity verification
$qr = $client->customers()->generateQr($customerId);
// returns: qr_type, format, payload, expires_at

// Pagination
$customers = $client->customers()->list(null, ['limit' => 50, 'page' => 1]);

// Model helpers
$customer->hasCompleteProfile();
$customer->canReceiveMarketing('email'); // 'email' | 'text' | 'phone'
$customer->getAge();
$customer->isInAgeRange(18, 65);
$customer->getSummary(); // ['display_name', 'complete_profile', 'marketing_consents']

// Customer groups
$groups = $client->customerGroups()->list();
```

## Order Processing & Cart

```php
// Calculate cart
$cartData = [
    'location' => $locationId,
    'items'    => [
        [
            'item'             => $itemId,
            'variant'          => $variantId,
            'qty'              => 2,
            'modifier_options' => [['modifier_option' => $modifierOptionId, 'qty' => 1]],
            'notes'            => 'Extra spicy',
        ],
    ],
    'discounts' => [['discount' => $discountId, 'value' => 10.00]],
    'charges'   => [['charge' => $chargeId, 'amount' => 5.00]],
];

$summary = $client->cart()->calculate($cartData);
echo 'Subtotal: $' . $summary->getSubtotal() . ' / Total: $' . $summary->getTotal();

// Place order
$orderData               = $cartData;
$orderData['order_info'] = [
    'dining_option' => $diningOptionId,
    'order_no'      => 'ORD' . time(),
    'pax'           => 2,
    'customer'      => $customerId,
    'status'        => 'pending',
];

$order = $client->cart()->submit($orderData);
echo 'Order #' . $order->getOrderNumber() . ' — $' . $order->getTotal();
```

## Advanced Order Management

```php
// Filtered list
$orders = $client->orders()->list(null, [
    'status'           => 'open',
    'financial_status' => 'paid',
    'location_id'      => $locationId,
    'detail'           => 'all',
    'limit'            => 25,
]);

// Collection helpers
$openOrders   = $orders->getByStatus('open');
$totalRevenue = $orders->getTotalRevenue();

// Status transitions
$client->orders()->close($orderId);
$client->orders()->reopen($orderId);
$client->orders()->setPending($orderId);
$client->orders()->setPendingPayment($orderId);

// Add payment
$client->orders()->addPayment($orderId, [
    'payment'    => $paymentMethodId,
    'amount'     => 50.00,
    'receipt_no' => 'RCP' . time(),
]);

// Kitchen / expedite (requires KDS subscription)
$client->orders()->setItemKitchenStatusFulfilled($orderId, $orderItemId);
$client->orders()->setItemExpediteStatusExpedited($orderId, $orderItemId);

// Date range
$recent = $client->orders()->listByDateRange(
    (new DateTime('-7 days'))->format('c'),
    (new DateTime())->format('c')
);

// Model helpers
$order->isPaid();
$order->isPartiallyPaid();
$order->isOpen();
$order->isClosed();
$order->getItemCount();
```

## Loyalty Programs

```php
// Programs and rewards
$programs = $client->loyalty()->getPrograms();
$rewards  = $client->loyalty()->getRewards($programId);

// Enroll
$member = $client->loyalty()->enrolMember($programId, ['customer' => $customerId]);

// Update member (v0.3.0)
$client->loyalty()->updateMember($programId, $memberId, ['expires_at' => '2027-12-31T23:59:59Z']);

// Search members (v0.3.0)
$members = $client->loyalty()->searchMembers($programId, ['customer_id' => $customerId]);
$member  = $client->loyalty()->getMemberByCustomer($programId, $customerId); // LoyaltyMember|null

// Points
$client->loyalty()->addPoints($programId, $memberId, 100, 'Welcome bonus', $locationId);
$client->loyalty()->subtractPoints($programId, $memberId, 50);
$client->loyalty()->awardPointsForOrder($programId, $memberId, $orderId, 150, $locationId);

// Redeem
if ($member->hasSufficientPoints($reward->getPoint())) {
    $client->loyalty()->redeemReward($programId, $memberId, $rewardId, $reward->getPoint(), $locationId);
}

// Transactions
$txns = $client->loyalty()->getMemberTransactions($programId, $memberId);
$txns = $client->loyalty()->getTransactionsByDateRange($programId, $startDate, $endDate);

// Collection helper
$members->getTotalPoints();
```

## Store Credit

```php
$balance = $client->storeCredit()->getCustomerBalance($customerId);
echo '$' . $balance->getStoreCredit();
$balance->hasStoreCredit();
$balance->hasSufficientCredit(25.00);

$client->storeCredit()->addCredit($customerId, 100.00, 'Refund', $locationId);
$client->storeCredit()->deductCredit($customerId, 25.50, 'Order payment', $locationId);

$topup = $client->storeCredit()->createTopup([
    'customer'       => $customerId,
    'topup_no'       => 'TOP' . time(),
    'topup_amount'   => 100.00,
    'payment'        => $paymentMethodId,
    'payment_amount' => 90.00,
]);

$txns = $client->storeCredit()->searchTransactions(['customer_id' => $customerId]);
$txns->getCreditAdditions();
$txns->getCreditDeductions();
$txns->getTotalCreditAmount();
```

## Reservations

```php
// Availability
$services = $client->reservations()->getAvailableServices($locationId, '2026-06-15', 4, 2);
foreach ($services as $service) {
    if ($service->hasAvailability()) {
        echo $service->getName() . ': ' . count($service->getAvailableTimes()) . " slots\n";
    }
}

// Book
$reservation = $client->reservations()->book([
    'location' => $locationId,
    'objects'  => [['object' => 'table_1', 'pax' => 4]],
    'reservation_info' => [
        'reservation_no'   => 'RES' . time(),
        'reservation_time' => $availableTimes[0]['time'],
        'service'          => $service->getId(),
        'customer'         => $customerId,
        'first_name'       => 'John',
        'last_name'        => 'Doe',
        'pax'              => 4,
        'adult'            => 4,
        'children'         => 0,
        'confirm_by'       => 'restaurant',
    ],
]);

// List and filter
$reservations     = $client->reservations()->list();
$upcoming         = $reservations->getUpcoming();
$totalPax         = $reservations->getTotalPax();

// Supporting resources
$experiences   = $client->experiences()->list($locationId);
$tableSections = $client->tableSections()->list($locationId);
```

## Discounts, Promotions & Vouchers

```php
// Discounts
$discounts = $client->discounts()->list($locationId);
foreach ($discounts as $discount) {
    if ($discount->isOpenDiscount()) {
        echo $discount->getName() . ": open\n";
    } elseif ($discount->isPercentDiscount()) {
        echo $discount->getName() . ': ' . $discount->getValue() . "%\n";
    }
}

// Promotions
$promotions = $client->promotions()->list($locationId);
foreach ($promotions as $promo) {
    echo $promo->getName() . ' — active: ' . ($promo->isActive() ? 'yes' : 'no') . "\n";
}

// Vouchers
$voucher = $client->vouchers()->create([
    'voucher_code'    => 'SAVE20',
    'type'            => 'discount',   // or 'promotion'
    'discount'        => $discountId,
    'max_redemptions' => 100,
    'start_date'      => (new DateTime())->format('c'),
    'end_date'        => (new DateTime('+30 days'))->format('c'),
]);

// Customer-specific voucher
$client->vouchers()->create([
    'voucher_code'    => 'VIP-' . $customerId,
    'applicable'      => 'customer',
    'customer'        => $customerId,
    'type'            => 'discount',
    'discount'        => $discountId,
    'max_redemptions' => 1,
    'start_date'      => (new DateTime())->format('c'),
]);

$results = $client->vouchers()->search(['voucher_code' => 'SAVE20']);
$qr      = $client->vouchers()->generateQr($voucherId);
$client->vouchers()->delete($voucherId);  // v0.3.0
$pools   = $client->vouchers()->getPools(); // v0.3.0

// Model helpers
$voucher->canBeRedeemed();
$voucher->isExpired();
```

## Inventory

```php
// Materials
$materials = $client->materials()->list($locationId);

// Stock levels
$stockLevels = $client->materials()->getStockLevels($locationId);
echo 'Out of stock: ' . count($stockLevels->getOutOfStock()) . "\n";
echo 'Low stock:    ' . count($stockLevels->getLowStock(10)) . "\n";
echo 'Total qty:    ' . $stockLevels->getTotalQuantity() . "\n";

foreach ($stockLevels as $stock) {
    if ($stock->isOutOfStock()) {
        echo $stock->getMaterialId() . ": OUT OF STOCK\n";
    }
}

// Stock takes
$stockTakes = $client->materials()->getStockTakes();
foreach ($stockTakes as $take) {
    echo $take->getId() . ' — ' . ($take->isOngoing() ? 'ongoing' : 'completed') . "\n";
    if ($take->isCompleted()) {
        echo '  Duration: ' . round($take->getDuration() / 3600, 2) . " hours\n";
    }
}
```
