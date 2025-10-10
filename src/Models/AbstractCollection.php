<?php
namespace Nava\Dinlr\Models;

/**
 * Abstract collection class
 */
abstract class AbstractCollection implements \ArrayAccess, \Countable, \Iterator, \JsonSerializable
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * Raw data for lazy loading
     * Performance: Store raw data and only instantiate models when accessed
     * @var array
     */
    protected $rawItems = [];

    /**
     * Track which items have been instantiated
     * Performance: Avoid re-instantiating models
     * @var array
     */
    protected $instantiated = [];

    /**
     * Cached serialized array
     * Performance: Cache toArray() result to avoid redundant serialization
     * @var array|null
     */
    protected $cachedArray = null;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var string
     */
    protected $modelClass;

    /**
     * Create a new collection
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->setItems($items);
    }

    /**
     * Set the items in the collection
     * Performance: Store raw data for lazy loading instead of instantiating immediately
     *
     * @param array $items
     * @return self
     */
    public function setItems(array $items): self
    {
        $this->rawItems     = $items;
        $this->items        = [];
        $this->instantiated = [];
        $this->position     = 0;
        $this->cachedArray  = null; // Invalidate cache

        return $this;
    }

    /**
     * Add an item to the collection
     *
     * @param mixed $item
     * @return self
     */
    public function add($item): self
    {
        $this->cachedArray = null; // Invalidate cache

        if (! $item instanceof $this->modelClass) {
            // Store raw data for lazy instantiation
            $index                     = count($this->rawItems);
            $this->rawItems[$index]    = $item;
            $this->instantiated[$index] = false;
        } else {
            // Already instantiated
            $index                     = count($this->rawItems);
            $this->rawItems[$index]    = null;
            $this->items[$index]       = $item;
            $this->instantiated[$index] = true;
        }

        return $this;
    }

    /**
     * Get item at index with lazy instantiation
     * Performance: Only create model objects when accessed
     *
     * @param int $index
     * @return mixed
     */
    protected function getItem(int $index)
    {
        // Check if already instantiated
        if (isset($this->instantiated[$index]) && $this->instantiated[$index]) {
            return $this->items[$index];
        }

        // Lazy instantiate from raw data
        if (isset($this->rawItems[$index])) {
            $this->items[$index]       = new $this->modelClass($this->rawItems[$index]);
            $this->instantiated[$index] = true;
            return $this->items[$index];
        }

        return null;
    }

    /**
     * Get all items in the collection
     * Performance: Instantiates all items (use sparingly, prefer iteration)
     *
     * @return array
     */
    public function all(): array
    {
        // Ensure all items are instantiated
        foreach ($this->rawItems as $index => $rawItem) {
            if (!isset($this->instantiated[$index]) || !$this->instantiated[$index]) {
                $this->getItem($index);
            }
        }

        return $this->items;
    }

    /**
     * Get the first item in the collection
     * Performance: Only instantiates the first item
     *
     * @return mixed|null
     */
    public function first()
    {
        return $this->getItem(0);
    }

    /**
     * Get the collection as an array
     * Performance: Use cached result if available, array_map for efficiency
     *
     * @return array
     */
    public function toArray(): array
    {
        // Return cached result if available
        if (null !== $this->cachedArray) {
            return $this->cachedArray;
        }

        // Performance: Use array_map for better performance than foreach
        $this->cachedArray = array_map(function($index) {
            $item = $this->getItem($index);
            return $item ? $item->toArray() : [];
        }, array_keys($this->rawItems));

        return $this->cachedArray;
    }

    /**
     * Convert the collection to JSON
     *
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Count the number of items in the collection (Countable)
     * Performance: Count raw items without instantiation
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->rawItems);
    }

    /**
     * Get the current item (Iterator)
     * Performance: Lazy instantiation during iteration
     *
     * @return mixed
     */
    public function current()
    {
        return $this->getItem($this->position);
    }

    /**
     * Get the current position (Iterator)
     *
     * @return int
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Move to the next item (Iterator)
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Rewind the iterator (Iterator)
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Check if the current position is valid (Iterator)
     * Performance: Check raw items without instantiation
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->rawItems[$this->position]);
    }

    /**
     * Check if the offset exists (ArrayAccess)
     * Performance: Check raw items without instantiation
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->rawItems[$offset]);
    }

    /**
     * Get the item at the offset (ArrayAccess)
     * Performance: Lazy instantiation on access
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getItem($offset);
    }

    /**
     * Set the item at the offset (ArrayAccess)
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->cachedArray = null; // Invalidate cache

        if (is_null($offset)) {
            $this->add($value);
        } else {
            if (! $value instanceof $this->modelClass) {
                $this->rawItems[$offset]    = $value;
                $this->instantiated[$offset] = false;
            } else {
                $this->rawItems[$offset]    = null;
                $this->items[$offset]       = $value;
                $this->instantiated[$offset] = true;
            }
        }
    }

    /**
     * Unset the item at the offset (ArrayAccess)
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->cachedArray = null; // Invalidate cache

        unset($this->rawItems[$offset]);
        unset($this->items[$offset]);
        unset($this->instantiated[$offset]);
    }

    /**
     * Serialize to JSON (JsonSerializable)
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
