# Upgrade Guide: v0.2.x → v0.3.0

This release adds new API resources and fills in several missing methods. There are **no breaking changes** — all existing code continues to work.

---

## New Resources

### ComboGroup (`$client->comboGroups()`)

**Endpoint:** `onlineorder/combo-groups`

```php
// List all combo groups
$comboGroups = $client->comboGroups()->list();

// Filter by location
$comboGroups = $client->comboGroups()->list(null, ['location_id' => $locationId]);

// Convenience wrapper
$comboGroups = $client->comboGroups()->listForLocation($locationId);

// Single record
$comboGroup = $client->comboGroups()->get($comboGroupId);
```

**Model methods:**

| Method | Returns | Description |
|---|---|---|
| `getId()` | string | Combo group ID |
| `getName()` | string | Display name |
| `getMinSelection()` | int\|null | Minimum items required |
| `getMaxSelection()` | int\|null | Maximum items allowed |
| `getSort()` | int\|null | Display order |
| `isRequired()` | bool | True when `min_selection > 0` |
| `getComboGroupItems()` | array | Array of combo group item objects |
| `getLocations()` | array | Locations this group is available at |
| `getUpdatedAt()` | string\|null | ISO 8601 timestamp |

---

### ItemBlock (`$client->itemBlocks()`)

**Endpoints:** `onlineorder/item-blocks`, `onlineorder/item-variant-blocks`, `onlineorder/modifier-option-blocks`

All three block endpoints are accessed through a single resource class. `location_id` is **required** for all methods.

```php
// Blocked items at a location
$blockedItems = $client->itemBlocks()->getItemBlocks($locationId);

// Blocked item variants at a location
$blockedVariants = $client->itemBlocks()->getItemVariantBlocks($locationId);

// Blocked modifier options at a location
$blockedOptions = $client->itemBlocks()->getModifierOptionBlocks($locationId);
```

**All three methods accept optional pagination params:**

```php
$blockedItems = $client->itemBlocks()->getItemBlocks($locationId, null, [
    'limit'          => 50,
    'page'           => 1,
    'updated_at_min' => '2026-01-01T00:00:00Z',
]);
```

**`ItemBlockCollection` helper:**

```php
$blockedItemIds    = $blockedItems->getBlockedItemIds();    // array of item IDs with status 'blocked'
$blockedVariantIds = $blockedVariants->getBlockedVariantIds();
$blockedOptionIds  = $blockedOptions->getBlockedOptionIds();
```

**`ItemBlock` model methods:**

| Method | Description |
|---|---|
| `getId()` | Record ID |
| `getItemId()` | Item ID (`item` key) |
| `getLocationId()` | Location ID (`location` key) |
| `getBlockStatus()` | Raw status string |
| `isBlocked()` | True when status is `'blocked'` |
| `getUpdatedAt()` | ISO 8601 timestamp |

`ItemVariantBlock` adds `getItemVariantId()`. `ModifierOptionBlock` adds `getModifierId()` and `getModifierOptionId()`.

---

## New Methods on Existing Resources

### Customer

#### `delete($customerId, $restaurantId = null): bool`

```php
$client->customers()->delete($customerId);
```

#### `generateQr($customerId, $restaurantId = null): array`

Generates a unique, time-sensitive QR code for in-restaurant customer identity verification.

```php
$qr = $client->customers()->generateQr($customerId);
// ['qr_type' => '...', 'format' => '...', 'payload' => '...', 'expires_at' => '...']
```

#### `search()` — new `external_reference` field

```php
$results = $client->customers()->search(['external_reference' => 'EXT-12345']);
```

---

### Loyalty

#### `updateMember($programId, $memberId, $data, $restaurantId = null): LoyaltyMember`

```php
$member = $client->loyalty()->updateMember($programId, $memberId, [
    'expires_at' => '2027-12-31T23:59:59Z',
]);
```

Validates `expires_at` as a datetime if provided.

#### `searchMembers($programId, $params = [], $restaurantId = null): LoyaltyMemberCollection`

Search members by `customer_id` or standard pagination params (`limit`, `page`, `updated_at_min`).

```php
$members = $client->loyalty()->searchMembers($programId, [
    'customer_id' => $customerId,
    'limit'       => 10,
]);
```

#### `getMemberByCustomer()` — now uses search API

Previously fetched all members and filtered client-side. Now delegates to `searchMembers()` with `limit=1` — a single targeted request.

```php
$member = $client->loyalty()->getMemberByCustomer($programId, $customerId);
// Returns LoyaltyMember|null
```

---

### Voucher

#### `delete($voucherId, $restaurantId = null): bool`

```php
$client->vouchers()->delete($voucherId);
```

#### `getPools($restaurantId = null, $params = []): array`

```php
$pools = $client->vouchers()->getPools();
```

---

## Bug Fixes

| Area | Issue | Impact |
|---|---|---|
| `Customer::validateCustomerData()` | Sanitized values from `validateString()` / `validateEmail()` were discarded; unsanitized data was sent to the API | Medium |
| `Customer::validateSearchParams()` | Same discard issue; sanitized params were not applied | Medium |
| `Loyalty::validateTransactionSearchParams()` | `validateString()` return values for `location_id`, `order_id`, `member_id`, `app_id` were discarded | Medium |
| `CustomerApi::delete()` | Missing `validateString()` call on `$customerId` | Medium |
| `CustomerApi::getAllCustomers()` | Used inline pagination validation instead of `AbstractResource::validatePagination()` | Style |
| `ComboGroup::list()` | Sanitized `location_id` value was discarded | Medium |
| `ItemBlock` (all 3 methods) | Sanitized `$locationId` return value was discarded | Medium |
