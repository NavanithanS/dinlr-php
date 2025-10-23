<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Contracts\ResourceInterface;
use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\Order as OrderModel;
use Nava\Dinlr\Models\OrderCollection;

/**
 * Order resource with comprehensive validation
 */
class Order extends AbstractResource implements ResourceInterface
{
    /**
     * @var string
     */
    protected $resourcePath = 'onlineorder/orders';

    /**
     * List all orders
     *
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Query parameters
     * @return OrderCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function list(string $restaurantId = null, array $params = []): OrderCollection
    {
        $this->validateListParams($params);

        $path     = $this->buildPath($restaurantId);
        $response = $this->client->request('GET', $path, $params);

        return new OrderCollection($response['data'] ?? []);
    }

    /**
     * Get a single order
     *
     * @param string $orderId Order ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function get(string $orderId, string $restaurantId = null): OrderModel
    {
        $this->validateString($orderId, 'Order ID');

        $path     = $this->buildPath($restaurantId, $orderId);
        $response = $this->client->request('GET', $path);

        return new OrderModel($response['data'] ?? []);
    }

    /**
     * Update an order
     *
     * @param string $orderId Order ID
     * @param array $data Order data
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function update(string $orderId, array $data, string $restaurantId = null): OrderModel
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateOrderUpdateData($data);

        $path     = $this->buildPath($restaurantId, $orderId);
        $response = $this->client->request('PUT', $path, $data);

        return new OrderModel($response['data'] ?? []);
    }

    /**
     * Add a payment to an order
     *
     * @param string $orderId Order ID
     * @param array $data Payment data
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function addPayment(string $orderId, array $data, string $restaurantId = null): OrderModel
    {
        $this->validateString($orderId, 'Order ID');
        $this->validatePaymentData($data);

        $path     = $this->buildPath($restaurantId, "{$orderId}/payments");
        $response = $this->client->request('POST', $path, $data);

        return new OrderModel($response['data'] ?? []);
    }

    /**
     * Add a refund to an order
     *
     * @param string $orderId Order ID
     * @param array $data Refund data
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function addRefund(string $orderId, array $data, string $restaurantId = null): OrderModel
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateRefundData($data);

        $path     = $this->buildPath($restaurantId, "{$orderId}/refunds");
        $response = $this->client->request('POST', $path, $data);

        return new OrderModel($response['data'] ?? []);
    }

    /**
     * Close an order
     *
     * @param string $orderId Order ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function close(string $orderId, string $restaurantId = null): array
    {
        $this->validateString($orderId, 'Order ID');

        $path     = $this->buildPath($restaurantId, "{$orderId}/close");
        $response = $this->client->request('POST', $path);

        if (isset($response['errors'])) {
            return [
                'status'    => 'error',
                'title'     => $response['errors']['title'] ?? 'Order Change Failed',
                'message'   => $response['errors']['detail'] ?? 'Unknown error occurred',
                'errorCode' => $response['errors']['status'] ?? 0,
            ];
        }

        return [
            'status' => 'success',
            'data'   => new OrderModel($response['data'] ?? [])
        ];
    }

    /**
     * Reopen an order
     *
     * @param string $orderId Order ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function reopen(string $orderId, string $restaurantId = null): OrderModel
    {
        $this->validateString($orderId, 'Order ID');

        $path     = $this->buildPath($restaurantId, "{$orderId}/open");
        $response = $this->client->request('POST', $path);

        return new OrderModel($response['data'] ?? []);
    }

    /**
     * Cancel an order
     *
     * @param string $orderId Order ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function cancel(string $orderId, string $restaurantId = null): array
    {
        $this->validateString($orderId, 'Order ID');

        $path     = $this->buildPath($restaurantId, "{$orderId}/cancel");
        $response = $this->client->request('POST', $path);

        if (isset($response['errors'])) {
            return [
                'status'    => 'error',
                'title'     => $response['errors']['title'] ?? 'Order Change Failed',
                'message'   => $response['errors']['detail'] ?? 'Unknown error occurred',
                'errorCode' => $response['errors']['status'] ?? 0,
            ];
        }

        return [
            'status' => 'success',
            'data'   => new OrderModel($response['data'] ?? [])
        ];
    }

    /**
     * Set an order to pending status
     *
     * @param string $orderId Order ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function setPending(string $orderId, string $restaurantId = null): OrderModel
    {
        $this->validateString($orderId, 'Order ID');

        $path     = $this->buildPath($restaurantId, "{$orderId}/pending");
        $response = $this->client->request('POST', $path);

        return new OrderModel($response['data'] ?? []);
    }

    /**
     * Set an order to pending payment status
     *
     * @param string $orderId Order ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return OrderModel
     * @throws ApiException
     * @throws ValidationException
     */
    public function setPendingPayment(string $orderId, string $restaurantId = null): OrderModel
    {
        $this->validateString($orderId, 'Order ID');

        $path     = $this->buildPath($restaurantId, "{$orderId}/pending_payment");
        $response = $this->client->request('POST', $path);

        return new OrderModel($response['data'] ?? []);
    }

    /**
     * Set an order item's kitchen status to pending
     *
     * @param string $orderId Order ID
     * @param string $orderItemId Order item ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return bool
     * @throws ApiException
     * @throws ValidationException
     */
    public function setItemKitchenStatusPending(string $orderId, string $orderItemId, string $restaurantId = null): bool
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateString($orderItemId, 'Order Item ID');

        $path = $this->buildPath($restaurantId, "{$orderId}/order_items/{$orderItemId}/kitchen/pending");
        $this->client->request('POST', $path);

        return true;
    }

    /**
     * Set an order item's kitchen status to fulfilled
     *
     * @param string $orderId Order ID
     * @param string $orderItemId Order item ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return bool
     * @throws ApiException
     * @throws ValidationException
     */
    public function setItemKitchenStatusFulfilled(string $orderId, string $orderItemId, string $restaurantId = null): bool
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateString($orderItemId, 'Order Item ID');

        $path = $this->buildPath($restaurantId, "{$orderId}/order_items/{$orderItemId}/kitchen/fulfill");
        $this->client->request('POST', $path);

        return true;
    }

    /**
     * Set an order item's kitchen status to default
     *
     * @param string $orderId Order ID
     * @param string $orderItemId Order item ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return bool
     * @throws ApiException
     * @throws ValidationException
     */
    public function setItemKitchenStatusDefault(string $orderId, string $orderItemId, string $restaurantId = null): bool
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateString($orderItemId, 'Order Item ID');

        $path = $this->buildPath($restaurantId, "{$orderId}/order_items/{$orderItemId}/kitchen/default");
        $this->client->request('POST', $path);

        return true;
    }

    /**
     * Set an order item's expedite status to pending
     *
     * @param string $orderId Order ID
     * @param string $orderItemId Order item ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return bool
     * @throws ApiException
     * @throws ValidationException
     */
    public function setItemExpediteStatusPending(string $orderId, string $orderItemId, string $restaurantId = null): bool
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateString($orderItemId, 'Order Item ID');

        $path = $this->buildPath($restaurantId, "{$orderId}/order_items/{$orderItemId}/expedite/pending");
        $this->client->request('POST', $path);

        return true;
    }

    /**
     * Set an order item's expedite status to expedited
     *
     * @param string $orderId Order ID
     * @param string $orderItemId Order item ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return bool
     * @throws ApiException
     * @throws ValidationException
     */
    public function setItemExpediteStatusExpedited(string $orderId, string $orderItemId, string $restaurantId = null): bool
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateString($orderItemId, 'Order Item ID');

        $path = $this->buildPath($restaurantId, "{$orderId}/order_items/{$orderItemId}/expedite/expedite");
        $this->client->request('POST', $path);

        return true;
    }

    /**
     * Set an order item's expedite status to default
     *
     * @param string $orderId Order ID
     * @param string $orderItemId Order item ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return bool
     * @throws ApiException
     * @throws ValidationException
     */
    public function setItemExpediteStatusDefault(string $orderId, string $orderItemId, string $restaurantId = null): bool
    {
        $this->validateString($orderId, 'Order ID');
        $this->validateString($orderItemId, 'Order Item ID');

        $path = $this->buildPath($restaurantId, "{$orderId}/order_items/{$orderItemId}/expedite/default");
        $this->client->request('POST', $path);

        return true;
    }

    // ==================== CONVENIENCE METHODS ====================

    /**
     * Get orders for a specific location
     *
     * @param string $locationId Location ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional query parameters
     * @return OrderCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function listForLocation(string $locationId, string $restaurantId = null, array $params = []): OrderCollection
    {
        $this->validateString($locationId, 'Location ID');

        $params['location_id'] = $locationId;
        return $this->list($restaurantId, $params);
    }

    /**
     * Get orders for a specific customer
     *
     * @param string $customerId Customer ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional query parameters
     * @return OrderCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function listForCustomer(string $customerId, string $restaurantId = null, array $params = []): OrderCollection
    {
        $this->validateString($customerId, 'Customer ID');

        $params['customer_id'] = $customerId;
        return $this->list($restaurantId, $params);
    }

    /**
     * Get orders within a date range
     *
     * @param string $startDate Start date (ISO 8601)
     * @param string $endDate End date (ISO 8601)
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional query parameters
     * @return OrderCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function listByDateRange(string $startDate, string $endDate, string $restaurantId = null, array $params = []): OrderCollection
    {
        $this->validateDate($startDate, 'Start date', 'c');
        $this->validateDate($endDate, 'End date', 'c');

        // Validate date range is not too large (API limit: 32 days)
        $start = new \DateTime($startDate);
        $end   = new \DateTime($endDate);
        $diff  = $start->diff($end);

        if ($diff->days > 32) {
            throw new ValidationException('Date range cannot exceed 32 days');
        }

        $params['created_at_min'] = $startDate;
        $params['created_at_max'] = $endDate;

        return $this->list($restaurantId, $params);
    }

    /**
     * Get orders by status
     *
     * @param string $status Order status
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional query parameters
     * @return OrderCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function listByStatus(string $status, string $restaurantId = null, array $params = []): OrderCollection
    {
        $validStatuses = ['pending', 'open', 'closed', 'cancelled', 'pending_payment', 'cancelled_payment'];

        if (! in_array($status, $validStatuses)) {
            throw new ValidationException('Invalid order status. Must be one of: ' . implode(', ', $validStatuses));
        }

        $params['status'] = $status;
        return $this->list($restaurantId, $params);
    }

    // ==================== VALIDATION METHODS ====================

    /**
     * Validate list parameters
     */
    private function validateListParams(array $params): void
    {
        $this->validatePagination($params);

        // Validate optional parameters
        $stringFields = ['app_id', 'location_id', 'ids', 'order_no', 'customer_id'];
        foreach ($stringFields as $field) {
            if (isset($params[$field]) && ! empty($params[$field])) {
                $this->validateString($params[$field], $field);
            }
        }

        // Validate detail parameter
        if (isset($params['detail']) && 'all' !== $params['detail']) {
            throw new ValidationException('Detail parameter must be "all" if provided');
        }

        // Validate date parameters
        $dateFields = ['updated_at_min', 'created_at_min', 'created_at_max'];
        foreach ($dateFields as $field) {
            if (isset($params[$field])) {
                $this->validateDateTime($params[$field], $field);
            }
        }

        // Validate date range if both min and max are provided
        if (isset($params['created_at_min']) && isset($params['created_at_max'])) {
            $this->validateDateRangeForApi($params['created_at_min'], $params['created_at_max'], 32);
        }

        // Validate limit for detail=all
        if (isset($params['detail']) && 'all' === $params['detail'] && isset($params['limit'])) {
            if ($params['limit'] > 100) {
                throw new ValidationException('Limit cannot exceed 100 when detail=all');
            }
        }
    }

    /**
     * Validate order update data
     * Performance: Batch validation to collect all errors at once
     */
    private function validateOrderUpdateData(array $data): void
    {
        $errors = [];

        // Validate items if provided - batch error collection
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => $item) {
                try {
                    $this->validateOrderItem($item, "items[{$index}]");
                } catch (ValidationException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Validate charges if provided - batch error collection
        if (isset($data['charges']) && is_array($data['charges'])) {
            foreach ($data['charges'] as $index => $charge) {
                try {
                    $this->validateCharge($charge, "charges[{$index}]");
                } catch (ValidationException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Validate discounts if provided - batch error collection
        if (isset($data['discounts']) && is_array($data['discounts'])) {
            foreach ($data['discounts'] as $index => $discount) {
                try {
                    $this->validateDiscount($discount, "discounts[{$index}]");
                } catch (ValidationException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Validate payments if provided - batch error collection
        if (isset($data['payments']) && is_array($data['payments'])) {
            foreach ($data['payments'] as $index => $payment) {
                try {
                    $this->validatePaymentData($payment, "payments[{$index}]");
                } catch (ValidationException $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }

        // Validate order_info if provided
        if (isset($data['order_info'])) {
            try {
                $this->validateOrderInfo($data['order_info']);
            } catch (ValidationException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // Performance: Throw once with all errors
        if (! empty($errors)) {
            throw new ValidationException(
                'Order validation failed: ' . implode('; ', $errors),
                ['errors' => $errors]
            );
        }
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData(array $data, string $prefix = ''): void
    {
        $fieldPrefix = $prefix ? "{$prefix}." : '';

        $this->validateRequired($data, ['payment', 'amount']);

        $this->validateString($data['payment'], $fieldPrefix . 'payment');
        $this->validateNumeric($data['amount'], $fieldPrefix . 'amount', 0.01);

        if (isset($data['receipt_no'])) {
            $this->validateString($data['receipt_no'], $fieldPrefix . 'receipt_no', 10);
            if (! preg_match('/^[A-Za-z0-9]+$/', $data['receipt_no'])) {
                throw new ValidationException($fieldPrefix . 'receipt_no must be alphanumeric with no spaces');
            }
        }

        // Validate payment inputs if provided
        if (isset($data['payment_inputs']) && is_array($data['payment_inputs'])) {
            foreach ($data['payment_inputs'] as $index => $input) {
                $this->validateRequired($input, ['payment_input', 'value']);
                $this->validateString($input['payment_input'], $fieldPrefix . "payment_inputs[{$index}].payment_input");
                $this->validateString($input['value'], $fieldPrefix . "payment_inputs[{$index}].value");
            }
        }
    }

    /**
     * Validate refund data
     */
    private function validateRefundData(array $data): void
    {
        $this->validateRequired($data, ['refund_payments']);

        if (! is_array($data['refund_payments']) || empty($data['refund_payments'])) {
            throw new ValidationException('Refund payments must be a non-empty array');
        }

        if (isset($data['refund_no'])) {
            $this->validateString($data['refund_no'], 'refund_no', 10);
            if (! preg_match('/^[A-Za-z0-9]+$/', $data['refund_no'])) {
                throw new ValidationException('Refund number must be alphanumeric with no spaces');
            }
        }

        foreach ($data['refund_payments'] as $index => $refundPayment) {
            $this->validateRequired($refundPayment, ['payment', 'amount']);
            $this->validateString($refundPayment['payment'], "refund_payments[{$index}].payment");
            $this->validateNumeric($refundPayment['amount'], "refund_payments[{$index}].amount", 0.01);
        }
    }

    /**
     * Validate order item data
     */
    private function validateOrderItem(array $item, string $prefix): void
    {
        // Item ID is optional for open items
        if (isset($item['item'])) {
            $this->validateString($item['item'], "{$prefix}.item");
        }

        // Name is required for open items
        if (isset($item['name'])) {
            $this->validateString($item['name'], "{$prefix}.name");
        }

        // Quantity validation
        if (isset($item['qty'])) {
            $this->validateNumeric($item['qty'], "{$prefix}.qty", 0.01);
        }

        if (isset($item['qty_unit'])) {
            $this->validateNumeric($item['qty_unit'], "{$prefix}.qty_unit", 0.01);
        }

        // Price validation for open items
        if (isset($item['price'])) {
            $this->validateNumeric($item['price'], "{$prefix}.price", 0);
        }

        // Notes validation
        if (isset($item['notes'])) {
            $this->validateString($item['notes'], "{$prefix}.notes", 200);
        }

        // Variant validation
        if (isset($item['variant'])) {
            $this->validateString($item['variant'], "{$prefix}.variant");
        }

        // Modifier options validation - batch errors
        if (isset($item['modifier_options']) && is_array($item['modifier_options'])) {
            $modErrors = [];
            foreach ($item['modifier_options'] as $index => $modOption) {
                try {
                    $this->validateRequired($modOption, ['modifier_option']);
                    $this->validateString($modOption['modifier_option'], "{$prefix}.modifier_options[{$index}].modifier_option");

                    if (isset($modOption['qty'])) {
                        $this->validateNumeric($modOption['qty'], "{$prefix}.modifier_options[{$index}].qty", 1);
                    }
                } catch (ValidationException $e) {
                    $modErrors[] = $e->getMessage();
                }
            }

            if (! empty($modErrors)) {
                throw new ValidationException(implode('; ', $modErrors));
            }
        }
    }

    /**
     * Validate charge data
     */
    private function validateCharge(array $charge, string $prefix): void
    {
        $this->validateRequired($charge, ['charge', 'amount']);
        $this->validateString($charge['charge'], "{$prefix}.charge");
        $this->validateNumeric($charge['amount'], "{$prefix}.amount", 0);
    }

    /**
     * Validate discount data
     */
    private function validateDiscount(array $discount, string $prefix): void
    {
        $this->validateRequired($discount, ['discount']);
        $this->validateString($discount['discount'], "{$prefix}.discount");

        if (isset($discount['value'])) {
            $this->validateNumeric($discount['value'], "{$prefix}.value", 0);
        }
    }

    /**
     * Validate order info data
     */
    private function validateOrderInfo(array $orderInfo): void
    {
        // Validate string fields with length limits
        $stringFields = [
            'dining_option' => null,
            'order_no'      => 10,
            'order_ticket'  => 50,
            'notes'         => 200,
            'customer'      => null,
            'first_name'    => 50,
            'last_name'     => 50,
            'email'         => 50,
            'phone'         => 50,
            'timeslot'      => null,
            'delivery_zone' => null,
            'address1'      => 100,
            'address2'      => 100,
            'city'          => 100,
            'postal'        => 50,
        ];

        foreach ($stringFields as $field => $maxLength) {
            if (isset($orderInfo[$field]) && ! empty($orderInfo[$field])) {
                $this->validateString($orderInfo[$field], $field, $maxLength);
            }
        }

        // Validate email format
        if (! empty($orderInfo['email'])) {
            $this->validateEmail($orderInfo['email']);
        }

        // Validate numeric fields
        if (isset($orderInfo['pax'])) {
            $this->validateNumeric($orderInfo['pax'], 'pax', 1);
        }

        if (isset($orderInfo['address_lat'])) {
            $this->validateNumeric($orderInfo['address_lat'], 'address_lat', -90, 90);
        }

        if (isset($orderInfo['address_lng'])) {
            $this->validateNumeric($orderInfo['address_lng'], 'address_lng', -180, 180);
        }

        // Validate date fields
        $dateFields = ['timeslot_start', 'timeslot_end', 'order_at'];
        foreach ($dateFields as $field) {
            if (isset($orderInfo[$field])) {
                $this->validateDate($orderInfo[$field], $field, 'c');
            }
        }

        // Validate country code
        if (! empty($orderInfo['country'])) {
            if (strlen($orderInfo['country']) !== 2) {
                throw new ValidationException('Country must be a 2-character ISO Alpha-2 code');
            }
        }

        // Validate status
        if (isset($orderInfo['status'])) {
            $validStatuses = ['pending', 'pending_payment'];
            if (! in_array($orderInfo['status'], $validStatuses)) {
                throw new ValidationException('Order status must be one of: ' . implode(', ', $validStatuses));
            }
        }

        // Validate order number format
        if (isset($orderInfo['order_no'])) {
            if (! preg_match('/^[A-Za-z0-9]+$/', $orderInfo['order_no'])) {
                throw new ValidationException('Order number must be alphanumeric with no spaces');
            }
        }
    }
}
