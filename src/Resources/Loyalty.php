<?php
namespace Nava\Dinlr\Resources;

use Nava\Dinlr\Exception\ApiException;
use Nava\Dinlr\Exception\ValidationException;
use Nava\Dinlr\Models\LoyaltyMember;
use Nava\Dinlr\Models\LoyaltyMemberCollection;
use Nava\Dinlr\Models\LoyaltyProgram;
use Nava\Dinlr\Models\LoyaltyProgramCollection;
use Nava\Dinlr\Models\LoyaltyRewardCollection;
use Nava\Dinlr\Models\LoyaltyTransaction;
use Nava\Dinlr\Models\LoyaltyTransactionCollection;

/**
 * Loyalty resource with comprehensive validation
 */
class Loyalty extends AbstractResource
{
    /**
     * @var string
     */
    protected $resourcePath = 'onlineorder/loyalty';

    /**
     * Get all loyalty programs
     *
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Query parameters
     * @return LoyaltyProgramCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getPrograms(string $restaurantId = null, array $params = []): LoyaltyProgramCollection
    {
        $this->validatePagination($params);

        // Validate date parameter if provided
        if (isset($params['updated_at_min'])) {
            $this->validateDate($params['updated_at_min'], 'updated_at_min', 'c');
        }

        $path     = $this->buildPath($restaurantId, 'programs');
        $response = $this->client->request('GET', $path, $params);

        return new LoyaltyProgramCollection($response['data'] ?? []);
    }

    /**
     * Get a single loyalty program
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyProgram
     * @throws ApiException
     * @throws ValidationException
     */
    public function getProgram(string $loyaltyProgramId, string $restaurantId = null): LoyaltyProgram
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');

        $path     = $this->buildPath($restaurantId, "programs/{$loyaltyProgramId}");
        $response = $this->client->request('GET', $path);

        return new LoyaltyProgram($response['data'] ?? []);
    }

    /**
     * Get rewards for a loyalty program
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyRewardCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getRewards(string $loyaltyProgramId, string $restaurantId = null): LoyaltyRewardCollection
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/rewards");
        $response = $this->client->request('GET', $path);

        return new LoyaltyRewardCollection($response['data'] ?? []);
    }

    /**
     * Get members of a loyalty program
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Query parameters
     * @return LoyaltyMemberCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getMembers(string $loyaltyProgramId, string $restaurantId = null, array $params = []): LoyaltyMemberCollection
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validatePagination($params);

        // Validate date parameter if provided
        if (isset($params['updated_at_min'])) {
            $this->validateDate($params['updated_at_min'], 'updated_at_min', 'c');
        }

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/members");
        $response = $this->client->request('GET', $path, $params);

        return new LoyaltyMemberCollection($response['data'] ?? []);
    }

    /**
     * Get a single loyalty member
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $memberId Member ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyMember
     * @throws ApiException
     * @throws ValidationException
     */
    public function getMember(string $loyaltyProgramId, string $memberId, string $restaurantId = null): LoyaltyMember
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validateString($memberId, 'Member ID');

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/members/{$memberId}");
        $response = $this->client->request('GET', $path);

        return new LoyaltyMember($response['data'] ?? []);
    }

    /**
     * Enroll a customer in a loyalty program
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param array $memberData Member data
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyMember
     * @throws ApiException
     * @throws ValidationException
     */
    public function enrolMember(string $loyaltyProgramId, array $memberData, string $restaurantId = null): LoyaltyMember
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validateMemberData($memberData);

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/members");
        $response = $this->client->request('POST', $path, $memberData);

        return new LoyaltyMember($response['data'] ?? []);
    }

    /**
     * Update a loyalty member
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $memberId Member ID
     * @param array $memberData Member data (e.g. expires_at)
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyMember
     * @throws ApiException
     * @throws ValidationException
     */
    public function updateMember(string $loyaltyProgramId, string $memberId, array $memberData, string $restaurantId = null): LoyaltyMember
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validateString($memberId, 'Member ID');

        if (isset($memberData['expires_at'])) {
            $this->validateDateTime($memberData['expires_at'], 'expires_at');
        }

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/members/{$memberId}");
        $response = $this->client->request('PUT', $path, $memberData);

        return new LoyaltyMember($response['data'] ?? []);
    }

    /**
     * Search loyalty members
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param array $searchParams Search parameters (customer_id, limit, page, updated_at_min)
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyMemberCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function searchMembers(string $loyaltyProgramId, array $searchParams = [], string $restaurantId = null): LoyaltyMemberCollection
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validatePagination($searchParams);

        if (! empty($searchParams['customer_id'])) {
            $searchParams['customer_id'] = $this->validateString($searchParams['customer_id'], 'Customer ID');
        }

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/members/search");
        $response = $this->client->request('GET', $path, $searchParams);

        return new LoyaltyMemberCollection($response['data'] ?? []);
    }

    /**
     * Create a loyalty transaction
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param array $transactionData Transaction data
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyTransaction
     * @throws ApiException
     * @throws ValidationException
     */
    public function createTransaction(string $loyaltyProgramId, array $transactionData, string $restaurantId = null): LoyaltyTransaction
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validateTransactionData($transactionData);

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/transactions");
        $response = $this->client->request('POST', $path, $transactionData);

        return new LoyaltyTransaction($response['data'] ?? []);
    }

    /**
     * Search loyalty transactions
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param array $searchParams Search parameters
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyTransactionCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function searchTransactions(string $loyaltyProgramId, array $searchParams = [], string $restaurantId = null): LoyaltyTransactionCollection
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $searchParams = $this->validateTransactionSearchParams($searchParams);

        $path     = $this->buildPath($restaurantId, "{$loyaltyProgramId}/transactions/search");
        $response = $this->client->request('GET', $path, $searchParams);

        return new LoyaltyTransactionCollection($response['data'] ?? []);
    }

    // ==================== CONVENIENCE METHODS ====================

    /**
     * Add points to a member's account
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $memberId Member ID
     * @param int $points Points to add (must be positive)
     * @param string|null $notes Optional notes
     * @param string|null $locationId Optional location ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyTransaction
     * @throws ApiException
     * @throws ValidationException
     */
    public function addPoints(
        string $loyaltyProgramId,
        string $memberId,
        int $points,
        string $notes = null,
        string $locationId = null,
        string $restaurantId = null
    ): LoyaltyTransaction {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validateString($memberId, 'Member ID');
        $this->validateNumeric($points, 'Points', 1); // Must be positive

        if (null !== $notes) {
            $this->validateString($notes, 'Notes', 200);
        }

        if (null !== $locationId) {
            $this->validateString($locationId, 'Location ID');
        }

        $transactionData = [
            'member' => $memberId,
            'points' => $points,
        ];

        if ($notes) {
            $transactionData['notes'] = $notes;
        }

        if ($locationId) {
            $transactionData['location'] = $locationId;
        }

        return $this->createTransaction($loyaltyProgramId, $transactionData, $restaurantId);
    }

    /**
     * Subtract points from a member's account
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $memberId Member ID
     * @param int $points Points to subtract (must be positive, will be converted to negative)
     * @param string|null $notes Optional notes
     * @param string|null $locationId Optional location ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyTransaction
     * @throws ApiException
     * @throws ValidationException
     */
    public function subtractPoints(
        string $loyaltyProgramId,
        string $memberId,
        int $points,
        string $notes = null,
        string $locationId = null,
        string $restaurantId = null
    ): LoyaltyTransaction {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validateString($memberId, 'Member ID');
        $this->validateNumeric($points, 'Points', 1); // Must be positive

        if (null !== $notes) {
            $this->validateString($notes, 'Notes', 200);
        }

        if (null !== $locationId) {
            $this->validateString($locationId, 'Location ID');
        }

        $transactionData = [
            'member' => $memberId,
            'points' => -$points, // Convert to negative for subtraction
        ];

        if ($notes) {
            $transactionData['notes'] = $notes;
        }

        if ($locationId) {
            $transactionData['location'] = $locationId;
        }

        return $this->createTransaction($loyaltyProgramId, $transactionData, $restaurantId);
    }

    /**
     * Get member by customer ID
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $customerId Customer ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyMember|null
     * @throws ApiException
     * @throws ValidationException
     */
    public function getMemberByCustomer(string $loyaltyProgramId, string $customerId, string $restaurantId = null): ?LoyaltyMember
    {
        $this->validateString($loyaltyProgramId, 'Loyalty program ID');
        $this->validateString($customerId, 'Customer ID');

        $members = $this->searchMembers($loyaltyProgramId, ['customer_id' => $customerId, 'limit' => 1], $restaurantId);
        return $members->first();
    }

    /**
     * Get transactions for a specific member
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $memberId Member ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional search parameters
     * @return LoyaltyTransactionCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getMemberTransactions(
        string $loyaltyProgramId,
        string $memberId,
        string $restaurantId = null,
        array $params = []
    ): LoyaltyTransactionCollection {
        $this->validateString($memberId, 'Member ID');

        $params['member_id'] = $memberId;
        return $this->searchTransactions($loyaltyProgramId, $params, $restaurantId);
    }

    /**
     * Get transactions for a specific location
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $locationId Location ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional search parameters
     * @return LoyaltyTransactionCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getLocationTransactions(
        string $loyaltyProgramId,
        string $locationId,
        string $restaurantId = null,
        array $params = []
    ): LoyaltyTransactionCollection {
        $this->validateString($locationId, 'Location ID');

        $params['location_id'] = $locationId;
        return $this->searchTransactions($loyaltyProgramId, $params, $restaurantId);
    }

    /**
     * Get transactions for a specific order
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $orderId Order ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional search parameters
     * @return LoyaltyTransactionCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getOrderTransactions(
        string $loyaltyProgramId,
        string $orderId,
        string $restaurantId = null,
        array $params = []
    ): LoyaltyTransactionCollection {
        $this->validateString($orderId, 'Order ID');

        $params['order_id'] = $orderId;
        return $this->searchTransactions($loyaltyProgramId, $params, $restaurantId);
    }

    /**
     * Get transactions created by the current app
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional search parameters
     * @return LoyaltyTransactionCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getCurrentAppTransactions(
        string $loyaltyProgramId,
        string $restaurantId = null,
        array $params = []
    ): LoyaltyTransactionCollection {
        $params['app_id'] = 'current';
        return $this->searchTransactions($loyaltyProgramId, $params, $restaurantId);
    }

    /**
     * Get transactions within a date range
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $startDate Start date (ISO 8601)
     * @param string $endDate End date (ISO 8601)
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @param array $params Additional search parameters
     * @return LoyaltyTransactionCollection
     * @throws ApiException
     * @throws ValidationException
     */
    public function getTransactionsByDateRange(
        string $loyaltyProgramId,
        string $startDate,
        string $endDate,
        string $restaurantId = null,
        array $params = []
    ): LoyaltyTransactionCollection {
        $this->validateDate($startDate, 'Start date', 'c');
        $this->validateDate($endDate, 'End date', 'c');

        $params['created_at_min'] = $startDate;
        // Note: API might support created_at_max, but it's not documented

        return $this->searchTransactions($loyaltyProgramId, $params, $restaurantId);
    }

    /**
     * Award loyalty points for an order
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $memberId Member ID
     * @param string $orderId Order ID
     * @param int $points Points to award
     * @param string|null $locationId Location ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyTransaction
     * @throws ApiException
     * @throws ValidationException
     */
    public function awardPointsForOrder(
        string $loyaltyProgramId,
        string $memberId,
        string $orderId,
        int $points,
        string $locationId = null,
        string $restaurantId = null
    ): LoyaltyTransaction {
        $this->validateString($orderId, 'Order ID');

        $transactionData = [
            'member' => $memberId,
            'points' => $points,
            'order'  => $orderId,
            'notes'  => "Points awarded for order {$orderId}",
        ];

        if ($locationId) {
            $transactionData['location'] = $locationId;
        }

        return $this->createTransaction($loyaltyProgramId, $transactionData, $restaurantId);
    }

    /**
     * Redeem points for a reward
     *
     * @param string $loyaltyProgramId Loyalty program ID
     * @param string $memberId Member ID
     * @param string $rewardId Reward ID
     * @param int $pointsRequired Points required for the reward
     * @param string|null $locationId Location ID
     * @param string|null $restaurantId Restaurant ID (uses config default if null)
     * @return LoyaltyTransaction
     * @throws ApiException
     * @throws ValidationException
     */
    public function redeemReward(
        string $loyaltyProgramId,
        string $memberId,
        string $rewardId,
        int $pointsRequired,
        string $locationId = null,
        string $restaurantId = null
    ): LoyaltyTransaction {
        $this->validateString($rewardId, 'Reward ID');
        $this->validateNumeric($pointsRequired, 'Points required', 1);

        $transactionData = [
            'member' => $memberId,
            'points' => -$pointsRequired, // Negative for redemption
            'notes'  => "Reward redemption: {$rewardId}",
        ];

        if ($locationId) {
            $transactionData['location'] = $locationId;
        }

        return $this->createTransaction($loyaltyProgramId, $transactionData, $restaurantId);
    }

    // ==================== VALIDATION METHODS ====================

    /**
     * Validate member enrollment data
     */
    private function validateMemberData(array $memberData): void
    {
        $this->validateRequired($memberData, ['customer']);
        $this->validateString($memberData['customer'], 'Customer ID');
    }

    /**
     * Validate transaction data
     */
    private function validateTransactionData(array $transactionData): void
    {
        $this->validateRequired($transactionData, ['member', 'points']);

        $this->validateString($transactionData['member'], 'Member ID');

        if (! is_int($transactionData['points'])) {
            throw new ValidationException('Points must be an integer value');
        }

        // Points can be negative for redemptions, so no minimum validation

        if (isset($transactionData['location'])) {
            $this->validateString($transactionData['location'], 'Location ID');
        }

        if (isset($transactionData['order'])) {
            $this->validateString($transactionData['order'], 'Order ID');
        }

        if (isset($transactionData['notes'])) {
            $this->validateString($transactionData['notes'], 'Notes', 200);
        }
    }

    /**
     * Validate transaction search parameters
     *
     * @return array Sanitized parameters
     */
    private function validateTransactionSearchParams(array $params): array
    {
        $this->validatePagination($params);

        // Validate optional string parameters
        $stringFields = ['location_id', 'order_id', 'member_id', 'app_id'];
        foreach ($stringFields as $field) {
            if (isset($params[$field]) && ! empty($params[$field])) {
                $params[$field] = $this->validateString($params[$field], $field);
            }
        }

        // Validate date parameter
        if (isset($params['created_at_min'])) {
            $this->validateDate($params['created_at_min'], 'created_at_min', 'c');
        }

        return $params;
    }
}
