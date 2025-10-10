<?php
namespace Nava\Dinlr\Models;

/**
 * Order model
 */
class Order extends AbstractModel
{
    /**
     * Get the order ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->getAttribute('id');
    }

    /**
     * Get the location ID
     *
     * @return string|null
     */
    public function getLocationId(): ?string
    {
        return $this->getAttribute('location');
    }

    /**
     * Get the customer ID
     *
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->getAttribute('customer');
    }

    /**
     * Get the order number
     *
     * @return string|null
     */
    public function getOrderNumber(): ?string
    {
        return $this->getAttribute('order_no');
    }

    /**
     * Get the order ticket
     *
     * @return string|null
     */
    public function getOrderTicket(): ?string
    {
        return $this->getAttribute('order_ticket');
    }

    /**
     * Get the dining option ID
     *
     * @return string|null
     */
    public function getDiningOptionId(): ?string
    {
        return $this->getAttribute('dining_option');
    }

    /**
     * Get the dining option name
     *
     * @return string|null
     */
    public function getDiningOptionName(): ?string
    {
        return $this->getAttribute('dining_option_name');
    }

    /**
     * Get the number of persons
     *
     * @return int|null
     */
    public function getPax(): ?int
    {
        return $this->getAttribute('pax');
    }

    /**
     * Get the order subtotal
     *
     * @return float|null
     */
    public function getSubtotal(): ?float
    {
        return $this->getAttribute('subtotal');
    }

    /**
     * Get the order total
     *
     * @return float|null
     */
    public function getTotal(): ?float
    {
        return $this->getAttribute('total');
    }

    /**
     * Get the paid amount
     *
     * @return float|null
     */
    public function getPaid(): ?float
    {
        return $this->getAttribute('paid');
    }

    /**
     * Get the order status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->getAttribute('status');
    }

    /**
     * Get the financial status
     *
     * @return string|null
     */
    public function getFinancialStatus(): ?string
    {
        return $this->getAttribute('financial_status');
    }

    /**
     * Get the kitchen status
     *
     * @return string|null
     */
    public function getKitchenStatus(): ?string
    {
        return $this->getAttribute('kitchen_status');
    }

    /**
     * Get the expedite status
     *
     * @return string|null
     */
    public function getExpediteStatus(): ?string
    {
        return $this->getAttribute('expedite_status');
    }

    /**
     * Get the created timestamp
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getAttribute('created_at');
    }

    /**
     * Get the updated timestamp
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getAttribute('updated_at');
    }

    /**
     * Get the order notes
     *
     * @return string|null
     */
    public function getNotes(): ?string
    {
        return $this->getAttribute('notes');
    }

    /**
     * Get the void reason
     *
     * @return string|null
     */
    public function getVoidReason(): ?string
    {
        return $this->getAttribute('void_reason');
    }

    /**
     * Get the order items
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->getAttribute('items', []);
    }

    /**
     * Get the order discounts
     *
     * @return array
     */
    public function getDiscounts(): array
    {
        return $this->getAttribute('discounts', []);
    }

    /**
     * Get the order charges
     *
     * @return array
     */
    public function getCharges(): array
    {
        return $this->getAttribute('charges', []);
    }

    /**
     * Get the order taxes
     *
     * @return array
     */
    public function getTaxes(): array
    {
        return $this->getAttribute('taxes', []);
    }

    /**
     * Get the order payments
     *
     * @return array
     */
    public function getPayments(): array
    {
        return $this->getAttribute('payments', []);
    }

    /**
     * Get the order refunds
     *
     * @return array
     */
    public function getRefunds(): array
    {
        return $this->getAttribute('refunds', []);
    }

    /**
     * Check if the order is paid
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->getFinancialStatus() === 'paid';
    }

    /**
     * Check if the order is partially paid
     *
     * @return bool
     */
    public function isPartiallyPaid(): bool
    {
        return $this->getFinancialStatus() === 'partially_paid';
    }

    /**
     * Check if the order is unpaid
     *
     * @return bool
     */
    public function isUnpaid(): bool
    {
        return $this->getFinancialStatus() === 'unpaid';
    }

    /**
     * Check if the order is open
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->getStatus() === 'open';
    }

    /**
     * Check if the order is closed
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->getStatus() === 'closed';
    }

    /**
     * Check if the order is cancelled
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->getStatus() === 'cancelled';
    }
}
