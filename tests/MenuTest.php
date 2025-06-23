<?php
namespace Nava\Dinlr\Tests;

use Nava\Dinlr\Client;
use Nava\Dinlr\Util\SharedCache;
use PHPUnit\Framework\TestCase;

class MenuTest extends TestCase
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

        echo "\n\n✅ TEST CASE: Online Menu API";
        echo "\n==============================================================";
        echo "\nSetting up test configuration...";

        // Load config
        $this->testConfig = require __DIR__ . '/config.php';

        echo "\n• API URL: " . $this->testConfig['api_url'];
        echo "\n• Restaurant ID: " . $this->testConfig['restaurant_id'];
        echo "\n--------------------------------------------------------------";
    }

    /**
     * Test getting all menus
     */
    public function testGetMenus()
    {
        echo "\n\nSTEP 1: Testing get all menus";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        // Use the first location ID if available
        $locationId = null;
        $locations  = $client->locations()->list();
        SharedCache::$locations = $locations;
        if (count($locations) > 0) {
            $locationId = $locations->first()->getId();
            SharedCache::$locationId = $locationId;
            echo "\n• Using location ID: " . $locationId;
        }

        $menus = $client->menu()->list($locationId);

        echo "\n• Total menus: " . count($menus);

        if (count($menus) > 0) {
            $menu = $menus->first();
            echo "\n• First menu ID: " . $menu->getId();
            echo "\n• First menu name: " . $menu->getName();
            echo "\n• Sort order: " . $menu->getSort();
            echo "\n• Total items in menu: " . $menu->getItemCount();

            // Test items
            $items = $menu->getItems();
            if (count($items) > 0) {
                echo "\n\n• First item in menu:";
                echo "\n  - ID: " . $items[0]['id'];
                echo "\n  - Name: " . $items[0]['name'];
                echo "\n  - Item No: " . ($items[0]['item_no'] ?? 'N/A');
                echo "\n  - Description: " . ($items[0]['description'] ?? 'N/A');
                echo "\n  - Category: " . ($items[0]['category'] ?? 'N/A');
                echo "\n  - Variants: " . count($items[0]['variants'] ?? []);
                echo "\n  - Modifiers: " . count($items[0]['modifiers'] ?? []);

                // Test variants
                if (isset($items[0]['variants']) && count($items[0]['variants']) > 0) {
                    $variant = $items[0]['variants'][0];
                    echo "\n\n  First variant:";
                    echo "\n    - Name: " . $variant['name'];
                    echo "\n    - Price: " . $variant['price'];
                    echo "\n    - SKU: " . ($variant['sku'] ?? 'N/A');
                    echo "\n    - Show in menu: " . ($variant['show_in_menu'] ? 'Yes' : 'No');
                }
            }

            // Test times
            $times = $menu->getTimes();
            echo "\n\n• Menu availability:";
            if (count($times) > 0) {
                foreach ($times as $time) {
                    echo "\n  - " . $time['day'] . ": " . $time['start_time'] . " - " . $time['end_time'];
                }
            } else {
                echo "\n  - No time restrictions";
            }
        }

        echo "\n\n✓ Menus retrieved successfully";
        echo "\n---------------------------------------------------------------\n\n";

        $this->assertInstanceOf(\Nava\Dinlr\Models\MenuCollection::class, $menus);
    }

    /**
     * Test menu availability methods
     */
    public function testMenuAvailability()
    {
        echo "\n\nSTEP 2: Testing menu availability methods";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        $locationId = SharedCache::$locationId;

        $menus = $client->menu()->list($locationId);

        if (count($menus) > 0) {
            $menu = $menus->first();

            // Test day availability
            $testDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            echo "\n• Testing availability for each day:";
            foreach ($testDays as $day) {
                $isAvailable = $menu->isAvailableOnDay($day);
                echo "\n  - " . $day . ": " . ($isAvailable ? 'Available' : 'Not available');

                // Add assertion: isAvailableOnDay should return a boolean
                $this->assertIsBool($isAvailable, "isAvailableOnDay should return a boolean");

                if ($isAvailable) {
                    $timeInfo = $menu->getTimeForDay($day);
                    if ($timeInfo) {
                        echo " (" . $timeInfo['start_time'] . " - " . $timeInfo['end_time'] . ")";
                        // Add assertion: timeInfo should have start_time and end_time
                        $this->assertArrayHasKey('start_time', $timeInfo);
                        $this->assertArrayHasKey('end_time', $timeInfo);
                    }
                }
            }

            echo "\n\n✓ Menu availability methods working correctly";
        } else {
            $this->markTestSkipped('No menus available for testing');
        }
    }

    /**
     * Test menu with multiple items
     */
    public function testMenuItems()
    {
        echo "\n\nSTEP 3: Testing menu items structure";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        $locationId = SharedCache::$locationId;

        $menus = $client->menu()->list($locationId);

        if (count($menus) > 0) {
            foreach ($menus as $menu) {
                if ($menu->getItemCount() > 0) {
                    echo "\n• Analyzing menu: " . $menu->getName();
                    echo "\n• Total items: " . $menu->getItemCount();

                    $items              = $menu->getItems();
                    $itemsWithImage     = 0;
                    $itemsWithVariants  = 0;
                    $itemsWithModifiers = 0;
                    $totalVariants      = 0;

                    foreach ($items as $item) {
                        if (! empty($item['image'])) {
                            $itemsWithImage++;
                        }

                        if (isset($item['variants']) && count($item['variants']) > 0) {
                            $itemsWithVariants++;
                            $totalVariants += count($item['variants']);
                        }

                        if (isset($item['modifiers']) && count($item['modifiers']) > 0) {
                            $itemsWithModifiers++;
                        }
                    }

                    echo "\n• Items with images: " . $itemsWithImage;
                    echo "\n• Items with variants: " . $itemsWithVariants;
                    echo "\n• Items with modifiers: " . $itemsWithModifiers;
                    echo "\n• Total variants across all items: " . $totalVariants;

                    // Add assertion: items should be an array
                    $this->assertIsArray($items, 'Menu items should be an array');
                    // Add assertion: at least one item exists
                    $this->assertGreaterThan(0, count($items), 'Menu should have at least one item');

                    break; // Just analyze the first menu with items
                }
            }

            echo "\n\n✓ Menu items structure analyzed successfully";
        } else {
            $this->markTestSkipped('No menus available for testing');
        }
    }

    /**
     * Test menu without location ID
     */
    public function testMenuWithoutLocation()
    {
        echo "\n\nSTEP 4: Testing menu without location ID";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        echo "\n• Fetching menus without specifying location ID";

        try {
            $menus = $client->menu()->list();
            echo "\n• Total menus: " . count($menus);
            echo "\n✓ Menu API works without location ID";
            $this->assertInstanceOf(\Nava\Dinlr\Models\MenuCollection::class, $menus);
        } catch (\Nava\Dinlr\Exception\ApiException $e) {
            echo "\n• API requires location ID: " . $e->getMessage();
            echo "\n✓ API correctly requires location ID";
            $this->assertStringContainsString('Location', $e->getMessage());
        }
    }

    /**
     * Test menu variant pricing
     */
    public function testMenuVariantPricing()
    {
        echo "\n\nSTEP 5: Testing menu variant pricing";
        echo "\n--------------------------------------------------------------";

        $client = new Client($this->testConfig);

        $locationId = SharedCache::$locationId;

        $menus = $client->menu()->list($locationId);

        if (count($menus) > 0) {
            $foundPricing = false;

            foreach ($menus as $menu) {
                $items = $menu->getItems();

                foreach ($items as $item) {
                    if (isset($item['variants']) && count($item['variants']) > 0) {
                        echo "\n• Item: " . $item['name'];
                        echo "\n• Variants:";

                        foreach ($item['variants'] as $variant) {
                            echo "\n  - " . $variant['name'] . ": ";

                            if (isset($variant['price'])) {
                                echo "$" . $variant['price'];
                                $foundPricing = true;
                                // Add assertion: price should be numeric
                                $this->assertIsNumeric($variant['price'], 'Variant price should be numeric');
                            } else {
                                echo "Open price";
                            }

                            if (isset($variant['sku'])) {
                                echo " (SKU: " . $variant['sku'] . ")";
                            }

                            if (isset($variant['summary'])) {
                                echo "\n    Summary: " . $variant['summary'];
                            }
                        }

                        if ($foundPricing) {
                            break 2; // Exit both loops
                        }
                    }
                }
            }

            // Add assertion: foundPricing should be true if any variant price was found
            $this->assertTrue($foundPricing, 'At least one variant with pricing should exist in the menu');

            if ($foundPricing) {
                echo "\n\n✓ Variant pricing information retrieved successfully";
            } else {
                echo "\n\n• No variant pricing found in menus";
            }
        } else {
            $this->markTestSkipped('No menus available for testing');
        }
    }
}
