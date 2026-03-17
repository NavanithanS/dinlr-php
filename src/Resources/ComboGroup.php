<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\ComboGroup as ComboGroupModel;
use Nava\Dinlr\Models\ComboGroupCollection;

/**
 * Combo group resource
 */
class ComboGroup extends AbstractResource
{
    /**
     * @var string
     */
    protected $resourcePath = 'onlineorder/combo-groups';

    /**
     * List all combo groups
     *
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @param array $params Query parameters (location_id, limit, page, updated_at_min)
     * @return ComboGroupCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function list(string $restaurantId = null, array $params = []): ComboGroupCollection
    {
        $this->validatePagination($params);

        if (isset($params['location_id'])) {
            $params['location_id'] = $this->validateString($params['location_id'], 'Location ID');
        }

        $path     = $this->buildPath($restaurantId);
        $response = $this->client->request('GET', $path, $params);

        return new ComboGroupCollection($response['data'] ?? []);
    }

    /**
     * Get a single combo group
     *
     * @param string $comboGroupId Combo group ID
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @return ComboGroupModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function get(string $comboGroupId, string $restaurantId = null): ComboGroupModel
    {
        $this->validateString($comboGroupId, 'Combo Group ID');

        $path     = $this->buildPath($restaurantId, $comboGroupId);
        $response = $this->client->request('GET', $path);

        return new ComboGroupModel($response['data'] ?? []);
    }

    /**
     * List combo groups for a specific location (convenience method)
     *
     * @param string $locationId Location ID
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @param array $params Additional query parameters
     * @return ComboGroupCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function listForLocation(string $locationId, string $restaurantId = null, array $params = []): ComboGroupCollection
    {
        $params['location_id'] = $locationId;
        return $this->list($restaurantId, $params);
    }
}
