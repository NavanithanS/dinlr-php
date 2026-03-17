<?php
namespace Nava\Dinlr\Models;

/**
 * Modifier option block model - represents a blocked modifier option at a location
 */
class ModifierOptionBlock extends AbstractModel
{
    public function getId(): ?string
    {
        return $this->getAttribute('id');
    }

    public function getLocationId(): ?string
    {
        return $this->getAttribute('location');
    }

    public function getModifierId(): ?string
    {
        return $this->getAttribute('modifier');
    }

    public function getModifierOptionId(): ?string
    {
        return $this->getAttribute('modifier_option');
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
