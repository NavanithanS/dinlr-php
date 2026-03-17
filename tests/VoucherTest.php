<?php
namespace Nava\Dinlr\Tests;

use Nava\Dinlr\Client;
use PHPUnit\Framework\TestCase;

class VoucherTest extends TestCase
{
    /**
     * @var array
     */
    protected $testConfig;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        echo "\n\n✅ TEST CASE: Vouchers API";
        echo "\n==============================================================";
        echo "\nSetting up test configuration...";

        // Load config
        $this->testConfig = require __DIR__ . '/config.php';

        echo "\n• API URL: " . $this->testConfig['api_url'];
        echo "\n• Restaurant ID: " . $this->testConfig['restaurant_id'];
        echo "\n--------------------------------------------------------------";
    }

    /**
     * Test getting all vouchers
     */
    public function testGetVouchers()
    {
        echo "\n\nSTEP 1: Testing get all vouchers";
        echo "\n--------------------------------------------------------------";

        $client   = new Client($this->testConfig);
        $vouchers = $client->vouchers()->list();

        echo "\n• Total vouchers: " . count($vouchers);

        if (count($vouchers) > 0) {
            $voucher = $vouchers->first();
            echo "\n• First voucher ID: " . $voucher->getId();
            echo "\n• Voucher code: " . $voucher->getVoucherCode();
            echo "\n• Type: " . $voucher->getType();
            echo "\n• Applicable: " . ($voucher->getApplicable() ?: 'All');
            echo "\n• Redeemed: " . $voucher->getRedeemed();
            echo "\n• Max redemptions: " . ($voucher->getMaxRedemptions() ?: 'Unlimited');
            echo "\n• Start date: " . $voucher->getStartDate();
            echo "\n• End date: " . ($voucher->getEndDate() ?: 'No end date');
            echo "\n• Can be redeemed: " . ($voucher->canBeRedeemed() ? 'Yes' : 'No');

            if ($voucher->isDiscountVoucher()) {
                echo "\n• Discount ID: " . $voucher->getDiscountId();
            } elseif ($voucher->isPromotionVoucher()) {
                echo "\n• Promotion ID: " . $voucher->getPromotionId();
            }

            if ($voucher->isCustomerVoucher()) {
                echo "\n• Customer ID: " . $voucher->getCustomerId();
            }
        }

        echo "\n✓ Vouchers retrieved successfully";

        $this->assertInstanceOf(\Nava\Dinlr\Models\VoucherCollection::class, $vouchers);
    }

    /**
     * Test getting a single voucher
     */
    public function testGetSingleVoucher()
    {
        echo "\n\nSTEP 2: Testing get single voucher";
        echo "\n--------------------------------------------------------------";

        $client   = new Client($this->testConfig);
        $vouchers = $client->vouchers()->list();

        if (count($vouchers) > 0) {
            $voucherId = $vouchers->first()->getId();
            echo "\n• Testing with voucher ID: " . $voucherId;

            $voucher = $client->vouchers()->get($voucherId);

            echo "\n• Voucher code: " . $voucher->getVoucherCode();
            echo "\n• Type: " . $voucher->getType();
            echo "\n• Is discount voucher: " . ($voucher->isDiscountVoucher() ? 'Yes' : 'No');
            echo "\n• Is promotion voucher: " . ($voucher->isPromotionVoucher() ? 'Yes' : 'No');
            echo "\n• Is customer voucher: " . ($voucher->isCustomerVoucher() ? 'Yes' : 'No');
            echo "\n• Has unlimited redemptions: " . ($voucher->hasUnlimitedRedemptions() ? 'Yes' : 'No');
            echo "\n• Has no end date: " . ($voucher->hasNoEndDate() ? 'Yes' : 'No');
            echo "\n✓ Single voucher retrieved successfully";

            $this->assertInstanceOf(\Nava\Dinlr\Models\Voucher::class, $voucher);
            $this->assertEquals($voucherId, $voucher->getId());
        } else {
            $this->markTestSkipped('No vouchers available for testing');
        }
    }

    /**
     * Test searching vouchers by code
     */
    public function testSearchVouchersByCode()
    {
        echo "\n\nSTEP 3: Testing search vouchers by code";
        echo "\n--------------------------------------------------------------";

        $client   = new Client($this->testConfig);
        $vouchers = $client->vouchers()->list();

        if (count($vouchers) > 0) {
            $voucherCode = $vouchers->first()->getVoucherCode();
            echo "\n• Searching for voucher code: " . $voucherCode;

            $searchParams = [
                'voucher_code' => $voucherCode,
            ];

            $searchResults = $client->vouchers()->search($searchParams);

            echo "\n• Search results: " . count($searchResults) . " voucher(s) found";

            if (count($searchResults) > 0) {
                $result = $searchResults->first();
                echo "\n• Found voucher code: " . $result->getVoucherCode();
                echo "\n• Found voucher ID: " . $result->getId();
            }

            echo "\n✓ Voucher search by code working correctly";

            $this->assertInstanceOf(\Nava\Dinlr\Models\VoucherCollection::class, $searchResults);
            $this->assertGreaterThan(0, count($searchResults));
        } else {
            $this->markTestSkipped('No vouchers available for testing');
        }
    }

    /**
     * Test searching vouchers by customer
     */
    public function testSearchVouchersByCustomer()
    {
        echo "\n\nSTEP 4: Testing search vouchers by customer";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        // First find a customer voucher if any
        $vouchers             = $client->vouchers()->list();
        $customerVoucherFound = false;
        $customerId           = null;

        foreach ($vouchers as $voucher) {
            if ($voucher->isCustomerVoucher() && $voucher->getCustomerId()) {
                $customerVoucherFound = true;
                $customerId           = $voucher->getCustomerId();
                break;
            }
        }

        if ($customerVoucherFound) {
            echo "\n• Searching for customer ID: " . $customerId;

            $searchParams = [
                'customer_id' => $customerId,
            ];

            $searchResults = $client->vouchers()->search($searchParams);

            echo "\n• Search results: " . count($searchResults) . " voucher(s) found for customer";

            foreach ($searchResults as $result) {
                echo "\n• Voucher code: " . $result->getVoucherCode() . " (Customer: " . $result->getCustomerId() . ")";
            }

            echo "\n✓ Voucher search by customer working correctly";

            $this->assertInstanceOf(\Nava\Dinlr\Models\VoucherCollection::class, $searchResults);
        } else {
            echo "\n• No customer-specific vouchers found, skipping customer search test";
            $this->markTestSkipped('No customer vouchers available for testing');
        }
    }

    /**
     * Test creating a voucher
     */
    public function testCreateVoucher()
    {
        echo "\n\nSTEP 5: Testing create voucher";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        // First get a discount to use
        $locations  = $client->locations()->list();
        $locationId = null;
        if (count($locations) > 0) {
            $locationId = $locations->first()->getId();
        }

        $discounts = $client->discounts()->list($locationId);

        if (count($discounts) > 0) {
            $discountId  = $discounts->first()->getId();
            $voucherCode = 'TEST' . time(); // Generate unique code

            echo "\n• Creating discount voucher with code: " . $voucherCode;
            echo "\n• Using discount ID: " . $discountId;

            $voucherData = [
                'voucher_code'    => $voucherCode,
                'type'            => 'discount',
                'discount'        => $discountId,
                'max_redemptions' => 1,
                'start_date'      => (new \DateTime())->format('c'),
                'end_date'        => (new \DateTime('+7 days'))->format('c'),
            ];

            try {
                $newVoucher = $client->vouchers()->create($voucherData);

                echo "\n• Voucher created successfully";
                echo "\n• Voucher ID: " . $newVoucher->getId();
                echo "\n• Voucher code: " . $newVoucher->getVoucherCode();
                echo "\n• Type: " . $newVoucher->getType();
                echo "\n• Max redemptions: " . $newVoucher->getMaxRedemptions();
                echo "\n✓ Voucher creation successful";

                $this->assertInstanceOf(\Nava\Dinlr\Models\Voucher::class, $newVoucher);
                $this->assertEquals($voucherCode, $newVoucher->getVoucherCode());
                $this->assertEquals('discount', $newVoucher->getType());

            } catch (\Nava\Dinlr\Exception\ApiException $e) {
                echo "\n• API Error: " . $e->getMessage();
                echo "\n• Note: Create voucher may require specific permissions";
                $this->markTestSkipped('Unable to create voucher: ' . $e->getMessage());
            }
        } else {
            $this->markTestSkipped('No discounts available for creating test voucher');
        }
    }

    /**
     * Test updating a voucher
     */
    public function testUpdateVoucher()
    {
        echo "\n\nSTEP 6: Testing update voucher";
        echo "\n--------------------------------------------------------------";

        $client   = new Client($this->testConfig);
        $vouchers = $client->vouchers()->list();

        if (count($vouchers) > 0) {
            // Find a voucher that can be updated (preferably a test voucher)
            $voucherToUpdate = null;
            foreach ($vouchers as $voucher) {
                if (strpos($voucher->getVoucherCode(), 'TEST') === 0) {
                    $voucherToUpdate = $voucher;
                    break;
                }
            }

            if (! $voucherToUpdate) {
                $voucherToUpdate = $vouchers->first();
            }

            echo "\n• Updating voucher: " . $voucherToUpdate->getVoucherCode();
            echo "\n• Voucher ID: " . $voucherToUpdate->getId();

            $updateData = [
                'max_redemptions' => 5,
            ];

            try {
                $updatedVoucher = $client->vouchers()->update($voucherToUpdate->getId(), $updateData);

                echo "\n• Voucher updated successfully";
                echo "\n• New max redemptions: " . $updatedVoucher->getMaxRedemptions();
                echo "\n✓ Voucher update successful";

                $this->assertInstanceOf(\Nava\Dinlr\Models\Voucher::class, $updatedVoucher);
                $this->assertEquals(5, $updatedVoucher->getMaxRedemptions());

            } catch (\Nava\Dinlr\Exception\ApiException $e) {
                echo "\n• API Error: " . $e->getMessage();
                echo "\n• Note: Update voucher may require specific permissions";
                $this->markTestSkipped('Unable to update voucher: ' . $e->getMessage());
            }
        } else {
            $this->markTestSkipped('No vouchers available for testing');
        }
    }

    /**
     * Test vouchers with query parameters
     */
    public function testVouchersWithQueryParams()
    {
        echo "\n\nSTEP 7: Testing vouchers with query parameters";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        // Test with pagination
        $params = [
            'limit' => 5,
            'page'  => 1,
        ];

        echo "\n• Testing with limit: " . $params['limit'];
        echo "\n• Testing with page: " . $params['page'];

        $vouchers = $client->vouchers()->list(null, $params);

        echo "\n• Vouchers returned: " . count($vouchers) . " (max " . $params['limit'] . ")";
        echo "\n✓ Query parameters working correctly";

        $this->assertLessThanOrEqual($params['limit'], count($vouchers));
    }

    /**
     * Test deleting a voucher
     */
    public function testDeleteVoucher()
    {
        echo "\n\nSTEP 9: Testing delete voucher";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        // Create a temporary voucher to delete
        $locations  = $client->locations()->list();
        $locationId = count($locations) > 0 ? $locations->first()->getId() : null;
        $discounts  = $client->discounts()->list($locationId);

        if (count($discounts) === 0) {
            $this->markTestSkipped('No discounts available for creating a test voucher to delete.');
        }

        $discountId  = $discounts->first()->getId();
        $voucherCode = 'DELTEST' . time();

        try {
            $newVoucher = $client->vouchers()->create([
                'voucher_code'    => $voucherCode,
                'type'            => 'discount',
                'discount'        => $discountId,
                'max_redemptions' => 1,
                'start_date'      => (new \DateTime())->format('c'),
            ]);

            echo "\n• Created temp voucher: " . $newVoucher->getId();

            $result = $client->vouchers()->delete($newVoucher->getId());

            echo "\n• Delete returned: " . ($result ? 'true' : 'false');
            echo "\n✓ Voucher deleted successfully";

            $this->assertTrue($result);

            // Confirm it's gone
            try {
                $client->vouchers()->get($newVoucher->getId());
                $this->fail('Expected ApiException (404) after deletion');
            } catch (\Nava\Dinlr\Exception\ApiException $e) {
                $this->assertEquals(404, $e->getCode());
                echo "\n✓ Deleted voucher returns 404 as expected";
            }
        } catch (\Nava\Dinlr\Exception\ApiException $e) {
            echo "\n• API Error: " . $e->getMessage();
            $this->markTestSkipped('Unable to create/delete voucher: ' . $e->getMessage());
        }
    }

    /**
     * Test validation errors
     */
    public function testValidationErrors()
    {
        echo "\n\nSTEP 8: Testing validation errors";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        // Test missing required fields
        echo "\n• Testing missing required fields";

        try {
            $client->vouchers()->create([]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (\Nava\Dinlr\Exception\ValidationException $e) {
            echo "\n✓ ValidationException caught for missing required fields";
            $this->assertStringContainsString('required', strtolower($e->getMessage()));
        }

        // Test invalid type
        echo "\n• Testing invalid voucher type";

        try {
            $client->vouchers()->create([
                'voucher_code' => 'INVALID_TYPE',
                'type'         => 'invalid',
                'start_date'   => (new \DateTime())->format('c'),
            ]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (\Nava\Dinlr\Exception\ValidationException $e) {
            echo "\n✓ ValidationException caught for invalid type";
            $this->assertStringContainsString('Invalid voucher type', $e->getMessage());
        }

        // Test missing discount ID for discount voucher
        echo "\n• Testing missing discount ID for discount voucher";

        try {
            $client->vouchers()->create([
                'voucher_code' => 'NO_DISCOUNT_ID',
                'type'         => 'discount',
                'start_date'   => (new \DateTime())->format('c'),
            ]);
            $this->fail('Expected ValidationException was not thrown');
        } catch (\Nava\Dinlr\Exception\ValidationException $e) {
            echo "\n✓ ValidationException caught for missing discount ID";
            $this->assertStringContainsString('Discount ID is required', $e->getMessage());
        }

        echo "\n✓ All validation errors handled correctly";
    }
}
