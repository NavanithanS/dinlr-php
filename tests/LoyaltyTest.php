<?php
namespace Nava\Dinlr\Tests;

use Nava\Dinlr\Client;
use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class LoyaltyTest extends TestCase
{
    protected $testConfig;
    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        echo "\n\n✅ TEST CASE: Loyalty Programs API Comprehensive Testing";
        echo "\n==============================================================";
        echo "\nSetting up test configuration...";

        $this->testConfig = require __DIR__ . '/config.php';
        $this->client     = new Client($this->testConfig);

        echo "\n• API URL: " . $this->testConfig['api_url'];
        echo "\n• Restaurant ID: " . $this->testConfig['restaurant_id'];
        echo "\n--------------------------------------------------------------";
    }

    public function testGetLoyaltyPrograms()
    {
        echo "\n\nSTEP 1: Testing retrieve all loyalty programs";
        echo "\n--------------------------------------------------------------";

        try {
            $programs = $this->client->loyalty()->getPrograms();

            echo "\n• Total loyalty programs: " . count($programs);

            if (count($programs) > 0) {
                $program = $programs->first();
                echo "\n• First program ID: " . $program->getId();
                echo "\n• First program name: " . $program->getName();
                echo "\n• Term singular: " . $program->getTermSingle();
                echo "\n• Term plural: " . $program->getTermPlural();
                echo "\n• Updated at: " . $program->getUpdatedAt();
            }

            echo "\n✓ Loyalty programs retrieved successfully";
            $this->assertInstanceOf(\Nava\Dinlr\Models\LoyaltyProgramCollection::class, $programs);

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                echo "\n• No loyalty programs available or feature not enabled";
                $this->markTestSkipped('Loyalty programs not available: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testGetLoyaltyRewards()
    {
        echo "\n\nSTEP 2: Testing retrieve loyalty rewards";
        echo "\n--------------------------------------------------------------";

        try {
            $programs = $this->client->loyalty()->getPrograms();

            if (count($programs) === 0) {
                $this->markTestSkipped('No loyalty programs available for rewards testing');
                return;
            }

            $programId = $programs->first()->getId();
            echo "\n• Using loyalty program ID: " . $programId;

            $rewards = $this->client->loyalty()->getRewards($programId);

            echo "\n• Total rewards: " . count($rewards);

            if (count($rewards) > 0) {
                $reward = $rewards->first();
                echo "\n• First reward ID: " . $reward->getId();
                echo "\n• First reward name: " . $reward->getName();
                echo "\n• Points required: " . $reward->getPoint();
            }

            echo "\n✓ Loyalty rewards retrieved successfully";
            $this->assertInstanceOf(\Nava\Dinlr\Models\LoyaltyRewardCollection::class, $rewards);

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                echo "\n• No loyalty rewards available";
                $this->markTestSkipped('Loyalty rewards not available: ' . $e->getMessage());
            } else {
                throw $e;
            }
        }
    }

    public function testEnrolLoyaltyMember()
    {
        echo "\n\nSTEP 5: Testing enrol loyalty member";
        echo "\n--------------------------------------------------------------";

        try {
            $programs = $this->client->loyalty()->getPrograms();

            if (count($programs) === 0) {
                $this->markTestSkipped('No loyalty programs available for member enrollment testing');
                return;
            }

            $customers = $this->client->customers()->list(null, ['limit' => 10]);

            if (count($customers) === 0) {
                $this->markTestSkipped('No customers available for loyalty member enrollment');
                return;
            }

            $programId = $programs->first()->getId();
            $customer = $customers->first();
            $customerId = $customer->getId();

            echo "\n• Enrolling customer ID: " . $customerId;
            echo "\n• Customer name: " . $customer->getFullName();

            $memberData = ['customer' => $customerId];

            $newMember = $this->client->loyalty()->enrolMember($programId, $memberData);

            echo "\n• Member enrolled successfully";
            echo "\n• New member ID: " . $newMember->getId();
            echo "\n• Starting points: " . $newMember->getPoint();

            $this->assertInstanceOf(\Nava\Dinlr\Models\LoyaltyMember::class, $newMember);
            $this->assertEquals($customerId, $newMember->getCustomer());

        } catch (ApiException $e) {
            if ($e->getCode() === 404 || $e->getCode() === 409) {
                $this->markTestSkipped('Loyalty member enrollment not available: ' . $e->getMessage());
            } else {
                $this->fail('Loyalty member enrollment failed: ' . $e->getMessage());
            }
        }
    }

    public function testUpdateLoyaltyMember()
    {
        echo "\n\nSTEP 5b: Testing update loyalty member";
        echo "\n--------------------------------------------------------------";

        try {
            $programs = $this->client->loyalty()->getPrograms();
            if (count($programs) === 0) {
                $this->markTestSkipped('No loyalty programs available');
                return;
            }

            $programId = $programs->first()->getId();
            $members   = $this->client->loyalty()->getMembers($programId);

            if (count($members) === 0) {
                $this->markTestSkipped('No loyalty members available');
                return;
            }

            $member        = $members->first();
            $memberId      = $member->getId();
            $expiresAt     = (new \DateTime('+1 year'))->format('c');
            $updatedMember = $this->client->loyalty()->updateMember($programId, $memberId, ['expires_at' => $expiresAt]);

            echo "\n• Member updated successfully";
            echo "\n• Member ID: " . $updatedMember->getId();

            $this->assertInstanceOf(\Nava\Dinlr\Models\LoyaltyMember::class, $updatedMember);
            $this->assertEquals($memberId, $updatedMember->getId());

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Loyalty member update not available: ' . $e->getMessage());
            } else {
                $this->fail('Loyalty member update failed: ' . $e->getMessage());
            }
        }
    }

    public function testSearchLoyaltyMembers()
    {
        echo "\n\nSTEP 5c: Testing search loyalty members";
        echo "\n--------------------------------------------------------------";

        try {
            $programs = $this->client->loyalty()->getPrograms();
            if (count($programs) === 0) {
                $this->markTestSkipped('No loyalty programs available');
                return;
            }

            $programId = $programs->first()->getId();
            $members   = $this->client->loyalty()->getMembers($programId);

            if (count($members) === 0) {
                $this->markTestSkipped('No loyalty members available');
                return;
            }

            $customerId    = $members->first()->getCustomer();
            $searchResults = $this->client->loyalty()->searchMembers($programId, ['customer_id' => $customerId]);

            echo "\n• Search results: " . count($searchResults);
            echo "\n✓ Member search via API endpoint successful";

            $this->assertInstanceOf(\Nava\Dinlr\Models\LoyaltyMemberCollection::class, $searchResults);

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Loyalty member search not available: ' . $e->getMessage());
            } else {
                $this->fail('Loyalty member search failed: ' . $e->getMessage());
            }
        }
    }

    public function testCreateLoyaltyTransaction()
    {
        echo "\n\nSTEP 6: Testing create loyalty transaction";
        echo "\n--------------------------------------------------------------";

        try {
            $programs = $this->client->loyalty()->getPrograms();
            if (count($programs) === 0) {
                $this->markTestSkipped('No loyalty programs available');
                return;
            }

            $programId = $programs->first()->getId();
            $members = $this->client->loyalty()->getMembers($programId);

            if (count($members) === 0) {
                $this->markTestSkipped('No loyalty members available');
                return;
            }

            $member = $members->first();
            $memberId = $member->getId();

            $transactionData = [
                'member' => $memberId,
                'points' => 10,
                'notes' => 'Test transaction created by API test'
            ];

            $transaction = $this->client->loyalty()->createTransaction($programId, $transactionData);

            echo "\n• Transaction created successfully";
            echo "\n• Transaction ID: " . $transaction->getId();
            echo "\n• Points added: " . $transaction->getPoint();

            $this->assertInstanceOf(\Nava\Dinlr\Models\LoyaltyTransaction::class, $transaction);
            $this->assertEquals(10, $transaction->getPoint());

        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Loyalty transaction creation not available: ' . $e->getMessage());
            } else {
                $this->fail('Loyalty transaction creation failed: ' . $e->getMessage());
            }
        }
    }
}