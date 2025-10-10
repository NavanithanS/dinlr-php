<?php
namespace Nava\Dinlr;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ConfigException;
use Nava\Dinlr\Resources\Cart;
use Nava\Dinlr\Resources\Category;
use Nava\Dinlr\Resources\Charge;
use Nava\Dinlr\Resources\Customer;
use Nava\Dinlr\Resources\CustomerGroup;
use Nava\Dinlr\Resources\DiningOption;
use Nava\Dinlr\Resources\Discount;
use Nava\Dinlr\Resources\Experience;
use Nava\Dinlr\Resources\Floorplan;
use Nava\Dinlr\Resources\Item;
use Nava\Dinlr\Resources\Location;
use Nava\Dinlr\Resources\Loyalty;
use Nava\Dinlr\Resources\Material;
use Nava\Dinlr\Resources\Menu;
use Nava\Dinlr\Resources\Modifier;
use Nava\Dinlr\Resources\Order;
use Nava\Dinlr\Resources\PaymentMethod;
use Nava\Dinlr\Resources\Promotion;
use Nava\Dinlr\Resources\Reservation;
use Nava\Dinlr\Resources\Restaurant;
use Nava\Dinlr\Resources\StoreCredit;
use Nava\Dinlr\Resources\TableSection;
use Nava\Dinlr\Resources\Voucher;

/**
 * Main client class for Dinlr API
 */
class Client
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Resource instances
     */
    private $resources = [];

    /**
     * Response cache for GET requests
     * Performance: Reduces redundant API calls for static data
     * @var array
     */
    private $responseCache = [];

    /**
     * Cache TTL in seconds (5 minutes default)
     * @var int
     */
    private $cacheTtl = 300;

    /**
     * Create a new Dinlr API client
     *
     * @param Config|array $config Configuration options
     */
    public function __construct($config)
    {
        if (is_array($config)) {
            $config = new Config($config);
        }

        if (! $config instanceof Config) {
            throw new ConfigException('Config must be an array or Config instance');
        }

        $this->config = $config;

        $this->httpClient = new HttpClient([
            'base_uri'    => $this->config->getApiUrl(),
            'headers'     => [
                'Authorization' => 'Bearer ' . $this->config->getApiKey(),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Make a request to the Dinlr API
     * Performance: Implements caching for GET requests to reduce redundant API calls
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return array Response data
     * @throws ApiException
     */
    public function request(string $method, string $endpoint, array $params = []): array
    {
        // Performance: Check cache for GET requests
        if ('GET' === $method) {
            $cacheKey = $this->getCacheKey($endpoint, $params);
            $cached   = $this->getFromCache($cacheKey);

            if (null !== $cached) {
                if ($this->config->isDebug()) {
                    error_log("Dinlr API Cache Hit: {$endpoint}");
                }
                return $cached;
            }
        }

        if ($this->config->isDebug()) {
            $safeMethod   = $this->sanitizeForLogging($method);
            $safeEndpoint = $this->sanitizeForLogging($endpoint);
            error_log("Dinlr API Request: {$safeMethod} {$safeEndpoint}");
        }

        $options = [];

        if (! empty($params)) {
            if ('GET' === $method) {
                $options['query'] = $params;
            } else {
                $options['json'] = $params;
            }
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, $options);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if ($response->getStatusCode() >= 400) {
                throw new ApiException(
                    $data['message'] ?? 'API error',
                    $response->getStatusCode(),
                    null,
                    [
                        'endpoint'      => $endpoint,
                        'method'        => $method,
                        'response_data' => $data,
                    ]
                );
            }

            if ($this->config->isDebug()) {
                $safeData = $this->sanitizeResponseForLogging($data);
                error_log("Dinlr API Response: " . json_encode($safeData));
            }

            // Performance: Cache successful GET requests
            if ('GET' === $method) {
                $this->setCache($cacheKey, $data);
            }

            return $data;

        } catch (RequestException $e) {
            throw new ApiException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Get the restaurant resource
     *
     * @return Restaurant
     */
    public function restaurant(): Restaurant
    {
        if (! isset($this->resources['restaurant'])) {
            $this->resources['restaurant'] = new Restaurant($this);
        }

        return $this->resources['restaurant'];
    }

    /**
     * Get the location resource
     *
     * @return Location
     */
    public function locations(): Location
    {
        if (! isset($this->resources['location'])) {
            $this->resources['location'] = new Location($this);
        }

        return $this->resources['location'];
    }

    /**
     * Get the dining option resource
     *
     * @return DiningOption
     */
    public function diningOptions(): DiningOption
    {
        if (! isset($this->resources['dining_option'])) {
            $this->resources['dining_option'] = new DiningOption($this);
        }

        return $this->resources['dining_option'];
    }

    /**
     * Get the payment method resource
     *
     * @return PaymentMethod
     */
    public function paymentMethods(): PaymentMethod
    {
        if (! isset($this->resources['payment_method'])) {
            $this->resources['payment_method'] = new PaymentMethod($this);
        }

        return $this->resources['payment_method'];
    }

    /**
     * Get the charge resource
     *
     * @return Charge
     */
    public function charges(): Charge
    {
        if (! isset($this->resources['charge'])) {
            $this->resources['charge'] = new Charge($this);
        }

        return $this->resources['charge'];
    }

    /**
     * Get the item resource
     *
     * @return Item
     */
    public function items(): Item
    {
        if (! isset($this->resources['item'])) {
            $this->resources['item'] = new Item($this);
        }

        return $this->resources['item'];
    }

    /**
     * Get the modifier resource
     *
     * @return Modifier
     */
    public function modifiers(): Modifier
    {
        if (! isset($this->resources['modifier'])) {
            $this->resources['modifier'] = new Modifier($this);
        }

        return $this->resources['modifier'];
    }

    /**
     * Get the category resource
     *
     * @return Category
     */
    public function categories(): Category
    {
        if (! isset($this->resources['category'])) {
            $this->resources['category'] = new Category($this);
        }

        return $this->resources['category'];
    }

    /**
     * Get the discount resource
     *
     * @return Discount
     */
    public function discounts(): Discount
    {
        if (! isset($this->resources['discount'])) {
            $this->resources['discount'] = new Discount($this);
        }

        return $this->resources['discount'];
    }

    /**
     * Get the promotion resource
     *
     * @return Promotion
     */
    public function promotions(): Promotion
    {
        if (! isset($this->resources['promotion'])) {
            $this->resources['promotion'] = new Promotion($this);
        }

        return $this->resources['promotion'];
    }

    /**
     * Get the voucher resource
     *
     * @return Voucher
     */
    public function vouchers(): Voucher
    {
        if (! isset($this->resources['voucher'])) {
            $this->resources['voucher'] = new Voucher($this);
        }

        return $this->resources['voucher'];
    }

    /**
     * Get the menu resource
     *
     * @return Menu
     */
    public function menu(): Menu
    {
        if (! isset($this->resources['menu'])) {
            $this->resources['menu'] = new Menu($this);
        }

        return $this->resources['menu'];
    }

    /**
     * Get the customer resource
     *
     * @return Customer
     */
    public function customers(): Customer
    {
        if (! isset($this->resources['customer'])) {
            $this->resources['customer'] = new Customer($this);
        }

        return $this->resources['customer'];
    }

    /**
     * Get the customer group resource
     *
     * @return CustomerGroup
     */
    public function customerGroups(): CustomerGroup
    {
        if (! isset($this->resources['customer_group'])) {
            $this->resources['customer_group'] = new CustomerGroup($this);
        }

        return $this->resources['customer_group'];
    }

    /**
     * Get the loyalty resource
     *
     * @return Loyalty
     */
    public function loyalty(): Loyalty
    {
        if (! isset($this->resources['loyalty'])) {
            $this->resources['loyalty'] = new Loyalty($this);
        }

        return $this->resources['loyalty'];
    }

    /**
     * Get the store credit resource
     *
     * @return StoreCredit
     */
    public function storeCredit(): StoreCredit
    {
        if (! isset($this->resources['store_credit'])) {
            $this->resources['store_credit'] = new StoreCredit($this);
        }

        return $this->resources['store_credit'];
    }

    /**
     * Get the cart resource
     *
     * @return Cart
     */
    public function cart(): Cart
    {
        if (! isset($this->resources['cart'])) {
            $this->resources['cart'] = new Cart($this);
        }
        return $this->resources['cart'];
    }

    /**
     * Get the order resource
     *
     * @return Order
     */
    public function orders(): Order
    {
        if (! isset($this->resources['order'])) {
            $this->resources['order'] = new Order($this);
        }

        return $this->resources['order'];
    }

    /**
     * Get the experience resource
     *
     * @return Experience
     */
    public function experiences(): Experience
    {
        if (! isset($this->resources['experience'])) {
            $this->resources['experience'] = new Experience($this);
        }
        return $this->resources['experience'];
    }

    /**
     * Get the table section resource
     *
     * @return TableSection
     */
    public function tableSections(): TableSection
    {
        if (! isset($this->resources['table_section'])) {
            $this->resources['table_section'] = new TableSection($this);
        }
        return $this->resources['table_section'];
    }

    /**
     * Get the reservation resource
     *
     * @return Reservation
     */
    public function reservations(): Reservation
    {
        if (! isset($this->resources['reservation'])) {
            $this->resources['reservation'] = new Reservation($this);
        }
        return $this->resources['reservation'];
    }

    /**
     * Get the material resource
     *
     * @return Material
     */
    public function materials(): Material
    {
        if (! isset($this->resources['material'])) {
            $this->resources['material'] = new Material($this);
        }
        return $this->resources['material'];
    }

    /**
     * Get the floorplan resource
     *
     * @return Floorplan
     */
    public function floorplans(): Floorplan
    {
        if (! isset($this->resources['floorplan'])) {
            $this->resources['floorplan'] = new Floorplan($this);
        }
        return $this->resources['floorplan'];
    }

    /**
     * Get the configuration
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Generate cache key for a request
     * Performance: Fast hash-based key generation
     */
    private function getCacheKey(string $endpoint, array $params): string
    {
        return md5($endpoint . json_encode($params));
    }

    /**
     * Get data from cache if not expired
     * Performance: In-memory cache with TTL check
     */
    private function getFromCache(string $key): ?array
    {
        if (!isset($this->responseCache[$key])) {
            return null;
        }

        $cached = $this->responseCache[$key];

        // Check if cache has expired
        if ($cached['expires_at'] < time()) {
            unset($this->responseCache[$key]);
            return null;
        }

        return $cached['data'];
    }

    /**
     * Store data in cache with expiration
     * Performance: Simple array-based cache for request lifecycle
     */
    private function setCache(string $key, array $data): void
    {
        $this->responseCache[$key] = [
            'data'       => $data,
            'expires_at' => time() + $this->cacheTtl,
        ];
    }

    /**
     * Clear all cached responses
     * Performance: Allows manual cache invalidation when needed
     */
    public function clearCache(): void
    {
        $this->responseCache = [];
    }

    private function sanitizeForLogging(string $input): string
    {
        return preg_replace('/[\r\n\t\x00-\x1F\x7F]/', ' ', $input);
    }

    private function sanitizeResponseForLogging(array $data): array
    {
        $sensitiveKeys = ['access_token', 'password', 'secret', 'key'];
        return $this->recursiveRedact($data, $sensitiveKeys);
    }

    /**
     * Recursively redact sensitive keys from an array
     * Performance: Efficient single-pass redaction for debug logging
     *
     * @param array $data Data to redact
     * @param array $sensitiveKeys Keys to redact
     * @return array Redacted data
     */
    private function recursiveRedact(array $data, array $sensitiveKeys): array
    {
        foreach ($data as $key => $value) {
            // Check if key matches any sensitive pattern (case-insensitive)
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (stripos($key, $sensitiveKey) !== false) {
                    $data[$key] = '[REDACTED]';
                    continue 2; // Skip to next key, no need to check other sensitive patterns
                }
            }

            // Recursively redact nested arrays
            if (is_array($value)) {
                $data[$key] = $this->recursiveRedact($value, $sensitiveKeys);
            }
        }

        return $data;
    }
}
