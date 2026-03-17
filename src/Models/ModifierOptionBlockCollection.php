<?php
namespace Nava\Dinlr\Models;

/**
 * Modifier option block collection
 */
class ModifierOptionBlockCollection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $modelClass = ModifierOptionBlock::class;

    /**
     * Get all blocked modifier option IDs
     *
     * @return array
     */
    public function getBlockedOptionIds(): array
    {
        return array_values(array_filter(array_map(function (ModifierOptionBlock $block) {
            return $block->isBlocked() ? $block->getModifierOptionId() : null;
        }, $this->all())));
    }
}
