<?php
namespace Nava\Dinlr\Tests;

use Nava\Dinlr\Client;
use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Models\ComboGroupCollection;
use PHPUnit\Framework\TestCase;

class ComboGroupTest extends TestCase
{
    /**
     * @var array
     */
    protected $testConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testConfig = require __DIR__ . '/config.php';
    }

    /**
     * Test retrieving all combo groups
     */
    public function testGetComboGroups()
    {
        $client = new Client($this->testConfig);

        try {
            $comboGroups = $client->comboGroups()->list();

            $this->assertInstanceOf(ComboGroupCollection::class, $comboGroups);

            if (count($comboGroups) > 0) {
                $comboGroup = $comboGroups->first();
                $this->assertNotNull($comboGroup->getId());
                $this->assertNotNull($comboGroup->getName());
                $this->assertIsArray($comboGroup->getComboGroupItems());
                $this->assertIsArray($comboGroup->getLocations());
            }
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('No combo groups available for this restaurant.');
            }
            throw $e;
        }
    }

    /**
     * Test retrieving combo groups filtered by location
     */
    public function testGetComboGroupsForLocation()
    {
        $client = new Client($this->testConfig);

        try {
            $locations = $client->locations()->list();

            if (count($locations) === 0) {
                $this->markTestSkipped('No locations available.');
            }

            $locationId  = $locations->first()->getId();
            $comboGroups = $client->comboGroups()->listForLocation($locationId);

            $this->assertInstanceOf(ComboGroupCollection::class, $comboGroups);
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('No combo groups available for this location.');
            }
            throw $e;
        }
    }

    /**
     * Test retrieving a single combo group
     */
    public function testGetSingleComboGroup()
    {
        $client = new Client($this->testConfig);

        try {
            $comboGroups = $client->comboGroups()->list();

            if (count($comboGroups) === 0) {
                $this->markTestSkipped('No combo groups available.');
            }

            $firstId    = $comboGroups->first()->getId();
            $comboGroup = $client->comboGroups()->get($firstId);

            $this->assertNotNull($comboGroup->getId());
            $this->assertNotNull($comboGroup->getName());
            $this->assertIsArray($comboGroup->getComboGroupItems());
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('Combo group not found.');
            }
            throw $e;
        }
    }
}
