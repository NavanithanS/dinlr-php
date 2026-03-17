<?php
namespace Nava\Dinlr\Models;

/**
 * Item block collection
 */
class ItemBlockCollection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $modelClass = ItemBlock::class;

    /**
     * Get all blocked item IDs
     *
     * @return array
     */
    public function getBlockedItemIds(): array
    {
        return array_values(array_filter(array_map(function (ItemBlock $block) {
            return $block->isBlocked() ? $block->getItemId() : null;
        }, $this->all())));
    }
}
