<?php
namespace Nava\Dinlr\Models;

/**
 * Item variant block collection
 */
class ItemVariantBlockCollection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $modelClass = ItemVariantBlock::class;

    /**
     * Get all blocked item variant IDs
     *
     * @return array
     */
    public function getBlockedVariantIds(): array
    {
        return array_values(array_filter(array_map(function (ItemVariantBlock $block) {
            return $block->isBlocked() ? $block->getItemVariantId() : null;
        }, $this->all())));
    }
}
