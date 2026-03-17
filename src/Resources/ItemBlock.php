<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\ItemBlockCollection;
use Nava\Dinlr\Models\ItemVariantBlockCollection;
use Nava\Dinlr\Models\ModifierOptionBlockCollection;

/**
 * Item block resource - handles blocked items, item variants, and modifier options by location
 */
class ItemBlock extends AbstractResource
{
    /**
     * @var string
     */
    protected $resourcePath = 'onlineorder/item-blocks';

    /**
     * Retrieve all blocked items for a location
     *
     * @param string $locationId Location ID (required)
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @param array $params Query parameters (limit, page, updated_at_min)
     * @return ItemBlockCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getItemBlocks(string $locationId, string $restaurantId = null, array $params = []): ItemBlockCollection
    {
        $locationId = $this->validateString($locationId, 'Location ID');
        $this->validatePagination($params);

        $params['location_id'] = $locationId;
        $path                  = $this->buildPath($restaurantId);
        $response              = $this->client->request('GET', $path, $params);

        return new ItemBlockCollection($response['data'] ?? []);
    }

    /**
     * Retrieve all blocked item variants for a location
     *
     * @param string $locationId Location ID (required)
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @param array $params Query parameters (limit, page, updated_at_min)
     * @return ItemVariantBlockCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getItemVariantBlocks(string $locationId, string $restaurantId = null, array $params = []): ItemVariantBlockCollection
    {
        $locationId = $this->validateString($locationId, 'Location ID');
        $this->validatePagination($params);

        $params['location_id'] = $locationId;
        $path                  = str_replace('/item-blocks', '/item-variant-blocks', $this->buildPath($restaurantId));
        $response              = $this->client->request('GET', $path, $params);

        return new ItemVariantBlockCollection($response['data'] ?? []);
    }

    /**
     * Retrieve all blocked modifier options for a location
     *
     * @param string $locationId Location ID (required)
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @param array $params Query parameters (limit, page, updated_at_min)
     * @return ModifierOptionBlockCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getModifierOptionBlocks(string $locationId, string $restaurantId = null, array $params = []): ModifierOptionBlockCollection
    {
        $locationId = $this->validateString($locationId, 'Location ID');
        $this->validatePagination($params);

        $params['location_id'] = $locationId;
        $path                  = str_replace('/item-blocks', '/modifier-option-blocks', $this->buildPath($restaurantId));
        $response              = $this->client->request('GET', $path, $params);

        return new ModifierOptionBlockCollection($response['data'] ?? []);
    }
}
