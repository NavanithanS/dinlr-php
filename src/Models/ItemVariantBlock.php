<?php
namespace Nava\Dinlr\Models;

/**
 * Item variant block model - represents a blocked item variant at a location
 */
class ItemVariantBlock extends AbstractModel
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

    public function getItemVariantId(): ?string
    {
        return $this->getAttribute('item_variant');
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
