<?php
namespace Nava\Dinlr\Tests;

use Nava\Dinlr\Client;
use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\ItemBlockCollection;
use Nava\Dinlr\Models\ItemVariantBlockCollection;
use Nava\Dinlr\Models\ModifierOptionBlockCollection;
use PHPUnit\Framework\TestCase;

class ItemBlockTest extends TestCase
{
    /**
     * @var array
     */
    protected $testConfig;

    /**
     * @var string|null
     */
    protected $locationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testConfig = require __DIR__ . '/config.php';
    }

    /**
     * Get the first available location ID
     */
    private function getLocationId(): string
    {
        $client    = new Client($this->testConfig);
        $locations = $client->locations()->list();

        if (count($locations) === 0) {
            $this->markTestSkipped('No locations available.');
        }

        return $locations->first()->getId();
    }

    /**
     * Test retrieving blocked items for a location
     */
    public function testGetItemBlocks()
    {
        $client     = new Client($this->testConfig);
        $locationId = $this->getLocationId();

        try {
            $blocks = $client->itemBlocks()->getItemBlocks($locationId);

            $this->assertInstanceOf(ItemBlockCollection::class, $blocks);
            $this->assertIsArray($blocks->getBlockedItemIds());

            if (count($blocks) > 0) {
                $block = $blocks->first();
                $this->assertNotNull($block->getId());
                $this->assertNotNull($block->getLocationId());
                $this->assertNotNull($block->getItemId());
                $this->assertIsBool($block->isBlocked());
            }
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('No item blocks available for this location.');
            }
            throw $e;
        }
    }

    /**
     * Test retrieving blocked item variants for a location
     */
    public function testGetItemVariantBlocks()
    {
        $client     = new Client($this->testConfig);
        $locationId = $this->getLocationId();

        try {
            $blocks = $client->itemBlocks()->getItemVariantBlocks($locationId);

            $this->assertInstanceOf(ItemVariantBlockCollection::class, $blocks);
            $this->assertIsArray($blocks->getBlockedVariantIds());

            if (count($blocks) > 0) {
                $block = $blocks->first();
                $this->assertNotNull($block->getId());
                $this->assertNotNull($block->getItemVariantId());
                $this->assertIsBool($block->isBlocked());
            }
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('No item variant blocks available for this location.');
            }
            throw $e;
        }
    }

    /**
     * Test retrieving blocked modifier options for a location
     */
    public function testGetModifierOptionBlocks()
    {
        $client     = new Client($this->testConfig);
        $locationId = $this->getLocationId();

        try {
            $blocks = $client->itemBlocks()->getModifierOptionBlocks($locationId);

            $this->assertInstanceOf(ModifierOptionBlockCollection::class, $blocks);
            $this->assertIsArray($blocks->getBlockedOptionIds());

            if (count($blocks) > 0) {
                $block = $blocks->first();
                $this->assertNotNull($block->getId());
                $this->assertNotNull($block->getModifierOptionId());
                $this->assertIsBool($block->isBlocked());
            }
        } catch (ApiException $e) {
            if ($e->getCode() === 404) {
                $this->markTestSkipped('No modifier option blocks available for this location.');
            }
            throw $e;
        }
    }

    /**
     * Test that location_id is required
     */
    public function testLocationIdRequired()
    {
        $this->expectException(ValidationException::class);

        $client = new Client($this->testConfig);
        $client->itemBlocks()->getItemBlocks('');
    }
}
