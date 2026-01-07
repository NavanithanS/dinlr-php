<?php

namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Contracts\ResourceInterface;
use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\Item as ItemModel;
use Nava\Dinlr\Models\ItemCollection;

class Item extends AbstractResource implements ResourceInterface
{
    protected $resourcePath = 'onlineorder/items';

    /**
     * List all items
     *
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Query parameters (location_id, limit, page, updated_at_min, etc.)
     * @return ItemCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function list(string $restaurantId = null, array $params = []): ItemCollection
    {
        $this->validatePagination($params);

        if (isset($params['location_id'])) {
            $this->validateString($params['location_id'], 'Location ID');
        }

        $path     = $this->buildPath($restaurantId);
        $response = $this->client->request('GET', $path, $params);

        if (isset($response['errors'])) {
            $status = $response['errors']['status'] ?? 500;
            $detail = $response['errors']['detail'] ?? 'Unknown Dinlr API Error';

            throw new ApiException($detail, $status);
        }

        return new ItemCollection($response['data'] ?? []);
    }

    /**
     * Get a single item
     *
     * @param string $itemId Item ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return ItemModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function get(string $itemId, string $restaurantId = null): ItemModel
    {
        $this->validateString($itemId, 'Item ID');

        $path     = $this->buildPath($restaurantId, $itemId);
        $response = $this->client->request('GET', $path);

        return new ItemModel($response['data'] ?? []);
    }

    /**
     * List items for a specific location (convenience method)
     *
     * @param string $locationId Location ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional query parameters
     * @return ItemCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function listForLocation(string $locationId, string $restaurantId = null, array $params = []): ItemCollection
    {
        $params['location_id'] = $locationId;
        return $this->list($restaurantId, $params);
    }
}
