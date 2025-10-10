<?php
namespace Nava\Dinlr\Models;

class LoyaltyMemberCollection extends AbstractCollection
{
    protected $modelClass = LoyaltyMember::class;

    /**
     * Cache for customer ID to member index mapping
     * Performance: Avoids N+1 lookup pattern
     * @var array
     */
    private $customerIndex = null;

    /**
     * Cached total points
     * Performance: Avoid recalculating on every call
     * @var int|null
     */
    private $cachedTotalPoints = null;

    /**
     * Build customer index for fast lookups
     * Performance: O(n) build once, O(1) lookups thereafter
     */
    private function buildCustomerIndex(): void
    {
        if (null !== $this->customerIndex) {
            return; // Already built
        }

        $this->customerIndex = [];

        // Build index from raw data without instantiating all models
        foreach ($this->rawItems as $index => $rawItem) {
            if (isset($rawItem['customer'])) {
                $this->customerIndex[$rawItem['customer']] = $index;
            }
        }
    }

    /**
     * Find member by customer ID
     * Performance: Uses indexed lookup instead of iterating all items
     *
     * @param string $customerId
     * @return LoyaltyMember|null
     */
    public function findByCustomer(string $customerId): ?LoyaltyMember
    {
        $this->buildCustomerIndex();

        // O(1) lookup instead of O(n) iteration
        if (isset($this->customerIndex[$customerId])) {
            return $this->getItem($this->customerIndex[$customerId]);
        }

        return null;
    }

    /**
     * Get total points across all members
     * Performance: Uses array_reduce and caches result
     *
     * @return int
     */
    public function getTotalPoints(): int
    {
        // Return cached value if available
        if (null !== $this->cachedTotalPoints) {
            return $this->cachedTotalPoints;
        }

        // Performance: Use array_reduce on raw data to avoid instantiating models
        $this->cachedTotalPoints = array_reduce($this->rawItems, function($total, $rawItem) {
            return $total + ($rawItem['point'] ?? 0);
        }, 0);

        return $this->cachedTotalPoints;
    }

    /**
     * Override setItems to invalidate caches
     */
    public function setItems(array $items): self
    {
        $this->customerIndex       = null;
        $this->cachedTotalPoints   = null;
        return parent::setItems($items);
    }

    /**
     * Override add to invalidate caches
     */
    public function add($item): self
    {
        $this->customerIndex     = null;
        $this->cachedTotalPoints = null;
        return parent::add($item);
    }
}
