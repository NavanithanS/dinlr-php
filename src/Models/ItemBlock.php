<?php
namespace Nava\Dinlr\Models;

/**
 * Item block model - represents a blocked item at a location
 */
class ItemBlock extends AbstractModel
{
    public function getId(): ?string
    {
        return $this->getAttribute('id');
    }

    public function getLocationId(): ?string
    {
        return $this->getAttribute('location');
    }

    public function getItemId(): ?string
    {
        return $this->getAttribute('item');
    }

    public function getBlockStatus(): ?string
    {
        return $this->getAttribute('block_status');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getAttribute('updated_at');
    }

    public function getCreatedAt(): ?string
    {
        return $this->getAttribute('created_at');
    }

    public function isBlocked(): bool
    {
        return $this->getBlockStatus() === 'blocked';
    }
}
