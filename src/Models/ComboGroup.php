<?php
namespace Nava\Dinlr\Models;

/**
 * Combo group model
 */
class ComboGroup extends AbstractModel
{
    public function getId(): ?string
    {
        return $this->getAttribute('id');
    }

    public function getName(): ?string
    {
        return $this->getAttribute('name');
    }

    public function getMinSelection(): ?int
    {
        return $this->getAttribute('min_selection');
    }

    public function getMaxSelection(): ?int
    {
        return $this->getAttribute('max_selection');
    }

    public function getSort(): ?int
    {
        return $this->getAttribute('sort');
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getAttribute('updated_at');
    }

    /**
     * Get the combo group items
     *
     * @return array Array of objects with 'item' (ID) and 'sort' keys
     */
    public function getComboGroupItems(): array
    {
        return $this->getAttribute('combo_group_items', []);
    }

    /**
     * Get location IDs this combo group is available in
     *
     * @return array
     */
    public function getLocations(): array
    {
        return $this->getAttribute('locations', []);
    }

    /**
     * Check if this combo group selection is required
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->getMinSelection() > 0;
    }
}
