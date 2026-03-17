# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.0] - 2026-03-17

### Added

**New Resources**

- `ComboGroup` resource (`onlineorder/combo-groups`) with `list()`, `get()`, and `listForLocation()` methods
- `ItemBlock` resource covering three block endpoints from a single class:
  - `getItemBlocks($locationId)` — blocked menu items by location
  - `getItemVariantBlocks($locationId)` — blocked item variants by location
  - `getModifierOptionBlocks($locationId)` — blocked modifier options by location
- New model classes: `ComboGroup`, `ComboGroupCollection`, `ItemBlock`, `ItemBlockCollection`, `ItemVariantBlock`, `ItemVariantBlockCollection`, `ModifierOptionBlock`, `ModifierOptionBlockCollection`
- `Client::comboGroups()` and `Client::itemBlocks()` factory methods

**Customer**

- `Customer::delete($customerId)` — delete a customer profile
- `Customer::generateQr($customerId)` — generate a time-sensitive QR code for identity verification
- `external_reference` added as a valid search field in both `Customer::search()` and `CustomerApi::searchCustomers()`

**Loyalty**

- `Loyalty::updateMember($programId, $memberId, $data)` — update a loyalty member (e.g. `expires_at`)
- `Loyalty::searchMembers($programId, $params)` — search members by `customer_id`, pagination, and `updated_at_min`

**Voucher**

- `Voucher::delete($voucherId)` — delete a voucher
- `Voucher::getPools()` — retrieve all voucher pools for a restaurant

### Fixed

- `Customer::validateCustomerData()` and `Customer::validateSearchParams()` were calling `validateString()` and `validateEmail()` but discarding the sanitized return values; all sanitized values are now captured and applied before sending data to the API
- `Loyalty::getMemberByCustomer()` was fetching all members and filtering client-side; it now uses the search API endpoint with `limit=1` for efficiency
- `Loyalty::validateTransactionSearchParams()` was discarding sanitized return values from `validateString()`; values are now captured and propagated to the outgoing request
- `CustomerApi::delete()` was missing ID validation; now calls `validateString()` consistent with all other delete methods
- `CustomerApi::getAllCustomers()` was using inline pagination validation instead of the shared `AbstractResource::validatePagination()`
- `ComboGroup::list()` and all three `ItemBlock` methods were discarding sanitized values returned by `validateString()` on location ID parameters

---

## [0.2.4] - previous

See git tags for earlier release history.
