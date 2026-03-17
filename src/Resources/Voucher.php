<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\Voucher as VoucherModel;
use Nava\Dinlr\Models\VoucherCollection;

/**
 * Voucher resource
 */
class Voucher extends AbstractResource
{
    /**
     * @var string
     */
    protected $resourcePath = 'onlineorder/vouchers';

    /**
     * List all vouchers
     *
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @param array $params Query parameters
     * @return VoucherCollection
     * @throws ApiException
     */
    public function list(string $restaurantId = null, array $params = []): VoucherCollection
    {
        $path = $this->buildPath($restaurantId);
        $response = $this->client->request('GET', $path, $params);

        return new VoucherCollection($response['data'] ?? []);
    }

    /**
     * Get a single voucher
     *
     * @param string $voucherId Voucher ID
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @return VoucherModel
     * @throws ApiException
     */
    public function get(string $voucherId, string $restaurantId = null): VoucherModel
    {
        $path = $this->buildPath($restaurantId, $voucherId);
        $response = $this->client->request('GET', $path);

        return new VoucherModel($response['data'] ?? []);
    }

    /**
     * Create a new voucher
     *
     * @param array $data Voucher data
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @return VoucherModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function create(array $data, string $restaurantId = null): VoucherModel
    {
        // Validate required parameters
        $this->validateRequired($data, ['voucher_code', 'type', 'start_date']);

        // Validate type
        if (!in_array($data['type'], ['discount', 'promotion'])) {
            throw new ValidationException(
                'Invalid voucher type. Must be "discount" or "promotion"',
                ['type' => 'Invalid value']
            );
        }

        // Validate discount or promotion ID based on type
        if ('discount' === $data['type'] && empty($data['discount'])) {
            throw new ValidationException(
                'Discount ID is required for discount voucher',
                ['discount' => 'Required']
            );
        }

        if ('promotion' === $data['type'] && empty($data['promotion'])) {
            throw new ValidationException(
                'Promotion ID is required for promotion voucher',
                ['promotion' => 'Required']
            );
        }

        // Validate customer ID if applicable is customer
        if (isset($data['applicable']) && 'customer' === $data['applicable'] && empty($data['customer'])) {
            throw new ValidationException(
                'Customer ID is required for customer voucher',
                ['customer' => 'Required']
            );
        }

        $path = $this->buildPath($restaurantId);
        $response = $this->client->request('POST', $path, $data);

        return new VoucherModel($response['data'] ?? []);
    }

    /**
     * Update a voucher
     *
     * @param string $voucherId Voucher ID
     * @param array $data Voucher data
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @return VoucherModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function update(string $voucherId, array $data, string $restaurantId = null): VoucherModel
    {
        // Validate type if provided
        if (isset($data['type']) && !in_array($data['type'], ['discount', 'promotion'])) {
            throw new ValidationException(
                'Invalid voucher type. Must be "discount" or "promotion"',
                ['type' => 'Invalid value']
            );
        }

        // Validate discount or promotion ID based on type
        if (isset($data['type'])) {
            if ('discount' === $data['type'] && isset($data['discount']) && empty($data['discount'])) {
                throw new ValidationException(
                    'Discount ID cannot be empty for discount voucher',
                    ['discount' => 'Required']
                );
            }

            if ('promotion' === $data['type'] && isset($data['promotion']) && empty($data['promotion'])) {
                throw new ValidationException(
                    'Promotion ID cannot be empty for promotion voucher',
                    ['promotion' => 'Required']
                );
            }
        }

        // Validate customer ID if applicable is customer
        if (isset($data['applicable']) && 'customer' === $data['applicable'] && isset($data['customer']) && empty($data['customer'])) {
            throw new ValidationException(
                'Customer ID cannot be empty for customer voucher',
                ['customer' => 'Required']
            );
        }

        $path = $this->buildPath($restaurantId, $voucherId);
        $response = $this->client->request('PUT', $path, $data);

        return new VoucherModel($response['data'] ?? []);
    }

    /**
     * Search for vouchers
     *
     * @param array $params Search parameters
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @return VoucherCollection
     * @throws ApiException
     */
    public function search(array $params, string $restaurantId = null): VoucherCollection
    {
        $path = $this->buildPath($restaurantId, 'search');
        $response = $this->client->request('GET', $path, $params);

        return new VoucherCollection($response['data'] ?? []);
    }

    /**
     * Generate a unique, time-sensitive QR code to verify a voucher identity in the restaurant
     *
     * @param string $voucherId Voucher ID
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @return array Response data containing QR code info
     * @throws ApiException
     */
    public function generateQr(string $voucherId, string $restaurantId = null): array
    {
        $path = $this->buildPath($restaurantId, $voucherId . '/qr');
        $response = $this->client->request('GET', $path);

        return $response['data'] ?? [];
    }

    /**
     * Delete a voucher
     *
     * @param string $voucherId Voucher ID
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @return bool
     * @throws ApiException
     */
    public function delete(string $voucherId, string $restaurantId = null): bool
    {
        $this->validateString($voucherId, 'Voucher ID');

        $path = $this->buildPath($restaurantId, $voucherId);
        $this->client->request('DELETE', $path);

        return true;
    }

    /**
     * Retrieve all voucher pools of the restaurant
     *
     * @param string|null $restaurantId Restaurant ID (optional, uses config if not provided)
     * @param array $params Query parameters
     * @return array List of voucher pools
     * @throws ApiException
     */
    public function getPools(string $restaurantId = null, array $params = []): array
    {
        $path = str_replace('/vouchers', '/voucher-pools', $this->buildPath($restaurantId));
        $response = $this->client->request('GET', $path, $params);

        return $response['data'] ?? [];
    }
}
