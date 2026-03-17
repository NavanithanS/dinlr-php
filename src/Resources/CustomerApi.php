<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\Customer as CustomerModel;
use Nava\Dinlr\Models\CustomerCollection;

/**
 * Production-ready Customer API Resource
 *
 * Handles all customer-related operations including CRUD operations,
 * search functionality, and customer group management.
 */
class CustomerApi extends AbstractResource
{
    /**
     * @var string API endpoint path
     */
    protected $resourcePath = 'onlineorder/customers';

    /**
     * Retrieve all customers with optional filtering and pagination
     *
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Query parameters for filtering and pagination
     * @return CustomerCollection Collection of customer objects
     * @throws ApiException On API errors
     */
    public function getAllCustomers(string $restaurantId = null, array $params = []): CustomerCollection
    {
        $this->validatePagination($params);

        $path     = $this->buildPath($restaurantId);
        $response = $this->client->request('GET', $path, $params);
        return new CustomerCollection($response['data'] ?? []);
    }

    /**
     * Create a new customer
     *
     * Requires at least one of: reference, first_name, or last_name
     *
     * @param array $customerData Customer information
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerModel Created customer object
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors
     */
    public function createCustomer(array $customerData, string $restaurantId = null): CustomerModel
    {
        // Validate required fields - at least one must be present
        if (
            empty($customerData['reference']) &&
            empty($customerData['first_name']) &&
            empty($customerData['last_name'])
        ) {
            throw new ValidationException(
                'At least one of the following fields is required: reference, first_name, last_name',
                ['required_one_of' => ['reference', 'first_name', 'last_name']]
            );
        }

        // Validate field lengths and formats
        $this->validateCustomerData($customerData);

        $path = $this->buildPath($restaurantId);
        $response = $this->client->request('POST', $path, $customerData);

        return new CustomerModel($response['data'] ?? []);
    }

    /**
     * Update an existing customer
     *
     * @param string $customerId Customer ID to update
     * @param array $customerData Updated customer information
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerModel Updated customer object
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors
     */
    public function updateCustomer(string $customerId, array $customerData, string $restaurantId = null): CustomerModel
    {
        if (empty($customerId)) {
            throw new ValidationException('Customer ID is required');
        }

        // Validate field lengths and formats for provided fields
        $this->validateCustomerData($customerData, false);

        $path = $this->buildPath($restaurantId, $customerId);
        $response = $this->client->request('PUT', $path, $customerData);

        return new CustomerModel($response['data'] ?? []);
    }

    /**
     * Delete a customer profile
     *
     * @param string $customerId Customer ID to delete
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return bool True on success
     * @throws ApiException On API errors
     */
    public function delete(string $customerId, string $restaurantId = null): bool
    {
        $this->validateString($customerId, 'Customer ID');

        $path = $this->buildPath($restaurantId, $customerId);
        $this->client->request('DELETE', $path);

        return true;
    }

    /**
     * Retrieve a single customer by ID
     *
     * @param string $customerId Customer ID to retrieve
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerModel Customer object
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors (404 if not found)
     */
    public function getCustomer(string $customerId, string $restaurantId = null): CustomerModel
    {
        if (empty($customerId)) {
            throw new ValidationException('Customer ID is required');
        }

        $path = $this->buildPath($restaurantId, $customerId);
        $response = $this->client->request('GET', $path);

        return new CustomerModel($response['data'] ?? []);
    }

    /**
     * Generate a unique, time-sensitive QR code to verify a customer identity in the restaurant
     *
     * @param string $customerId Customer ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return array Response data containing QR code info
     * @throws ApiException On API errors
     */
    public function generateQr(string $customerId, string $restaurantId = null): array
    {
        $path = $this->buildPath($restaurantId, $customerId . '/qr');
        $response = $this->client->request('GET', $path);

        return $response['data'] ?? [];
    }

    /**
     * Search customers by various criteria
     *
     * @param array $searchParams Search parameters (reference, email, phone)
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerCollection Collection of matching customers
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors
     */
    public function searchCustomers(array $searchParams, string $restaurantId = null): CustomerCollection
    {
        // Validate at least one search parameter is provided
        $validSearchFields = ['reference', 'email', 'phone', 'external_reference'];
        $hasValidSearchField = false;

        foreach ($validSearchFields as $field) {
            if (!empty($searchParams[$field])) {
                $hasValidSearchField = true;
                break;
            }
        }

        if (!$hasValidSearchField) {
            throw new ValidationException(
                'At least one search parameter is required: ' . implode(', ', $validSearchFields)
            );
        }

        // Validate email format if provided
        if (!empty($searchParams['email']) && !filter_var($searchParams['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }

        $path = $this->buildPath($restaurantId, 'search');
        $response = $this->client->request('GET', $path, $searchParams);

        return new CustomerCollection($response['data'] ?? []);
    }

    /**
     * Get customers by email (convenience method)
     *
     * @param string $email Email address to search for
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerCollection Collection of customers with matching email
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors
     */
    public function getCustomersByEmail(string $email, string $restaurantId = null): CustomerCollection
    {
        return $this->searchCustomers(['email' => $email], $restaurantId);
    }

    /**
     * Get customers by phone (convenience method)
     *
     * @param string $phone Phone number to search for
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerCollection Collection of customers with matching phone
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors
     */
    public function getCustomersByPhone(string $phone, string $restaurantId = null): CustomerCollection
    {
        return $this->searchCustomers(['phone' => $phone], $restaurantId);
    }

    /**
     * Get customers by reference (convenience method)
     *
     * @param string $reference Reference to search for
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerCollection Collection of customers with matching reference
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors
     */
    public function getCustomersByReference(string $reference, string $restaurantId = null): CustomerCollection
    {
        return $this->searchCustomers(['reference' => $reference], $restaurantId);
    }

    /**
     * Retrieve all customer groups
     *
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Query parameters for pagination
     * @return \Nava\Dinlr\Models\CustomerGroupCollection Collection of customer groups
     * @throws ApiException On API errors
     */
    public function getCustomerGroups(string $restaurantId = null, array $params = []): \Nava\Dinlr\Models\CustomerGroupCollection
    {
        $path = str_replace('/customers', '/customer-groups', $this->buildPath($restaurantId));
        $response = $this->client->request('GET', $path, $params);

        return new \Nava\Dinlr\Models\CustomerGroupCollection($response['data'] ?? []);
    }

    /**
     * Retrieve all customer custom fields
     *
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Query parameters
     * @return array List of custom fields
     * @throws ApiException On API errors
     */
    public function getCustomFields(string $restaurantId = null, array $params = []): array
    {
        $path = str_replace('/customers', '/customer-custom-fields', $this->buildPath($restaurantId));
        $response = $this->client->request('GET', $path, $params);

        return $response['data'] ?? [];
    }

    /**
     * Validate customer data fields
     *
     * @param array $data Customer data to validate
     * @param bool $isCreate Whether this is for creation (stricter validation)
     * @throws ValidationException On validation errors
     */
    private function validateCustomerData(array $data, bool $isCreate = true): void
    {
        $errors = [];

        // Validate field lengths
        $fieldLimits = [
            'reference' => 50,
            'first_name' => 50,
            'last_name' => 50,
            'company_name' => 50,
            'email' => 50,
            'phone' => 50,
            'postal' => 50,
            'address1' => 100,
            'address2' => 100,
            'city' => 100,
            'notes' => 200,
        ];

        foreach ($fieldLimits as $field => $maxLength) {
            if (isset($data[$field]) && strlen($data[$field]) > $maxLength) {
                $errors[$field] = "Field {$field} cannot exceed {$maxLength} characters";
            }
        }

        // Validate email format
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Validate date of birth format
        if (!empty($data['dob'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['dob']);
            if (!$date || $date->format('Y-m-d') !== $data['dob']) {
                $errors['dob'] = 'Date of birth must be in YYYY-MM-DD format';
            }
        }

        // Validate gender
        if (!empty($data['gender']) && !in_array($data['gender'], ['M', 'F'])) {
            $errors['gender'] = 'Gender must be M or F';
        }

        // Validate country code (should be ISO Alpha-2)
        if (!empty($data['country']) && strlen($data['country']) !== 2) {
            $errors['country'] = 'Country must be a 2-character ISO Alpha-2 code';
        }

        // Validate boolean fields
        $booleanFields = ['marketing_consent_email', 'marketing_consent_text', 'marketing_consent_phone'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field]) && !is_bool($data[$field])) {
                $errors[$field] = "Field {$field} must be a boolean value";
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }
    }

    /**
     * Get customers with advanced filtering options
     *
     * @param array $filters Advanced filter options
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return CustomerCollection Filtered customer collection
     * @throws ValidationException On validation errors
     * @throws ApiException On API errors
     */
    public function getCustomersWithFilters(array $filters = [], string $restaurantId = null): CustomerCollection
    {
        $params = [];

        // Handle pagination
        if (isset($filters['limit'])) {
            $params['limit'] = min(max((int) $filters['limit'], 1), 200);
        }

        if (isset($filters['page'])) {
            $params['page'] = max((int) $filters['page'], 1);
        }

        // Handle date filtering
        if (isset($filters['updated_after'])) {
            $params['updated_at_min'] = $filters['updated_after'];
        }

        return $this->getAllCustomers($restaurantId, $params);
    }

    /**
     * Bulk operations helper - check if customers exist
     *
     * @param array $customerIds Array of customer IDs to check
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return array Array of existing customer IDs
     * @throws ApiException On API errors
     */
    public function checkCustomersExist(array $customerIds, string $restaurantId = null): array
    {
        $existingCustomers = [];

        foreach ($customerIds as $customerId) {
            try {
                $customer = $this->getCustomer($customerId, $restaurantId);
                $existingCustomers[] = $customer->getId();
            } catch (ApiException $e) {
                if ($e->getCode() !== 404) {
                    throw $e; // Re-throw non-404 errors
                }
                // 404 means customer doesn't exist, skip it
            }
        }

        return $existingCustomers;
    }
}
