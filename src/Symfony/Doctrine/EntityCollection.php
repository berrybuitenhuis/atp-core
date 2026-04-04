<?php

namespace AtpCore\Symfony\Doctrine;

use AtpCore\Error;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @template T of object
 */
class EntityCollection
{
    protected ArrayCollection $collection;

    public function __construct(
        protected readonly string $entityClass,
        protected readonly ?array $results = [],
    ) {
        $this->collection = new ArrayCollection($results);
    }

    /**
     * Get number of records in collection
     *
     * @return int
     */
    public function count(): int
    {
        return ($this->collection->isEmpty()) ? 0 : $this->collection->count();
    }

    /**
     * Check if collection has results
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->collection->isEmpty() === false;
    }

    /**
     * Get first result of collection
     *
     * @return T|Error
     */
    public function first(): object
    {
        if ($this->collection->isEmpty()) {
            return new Error(messages: ["No results found for $this->entityClass"], stackTrace: debug_backtrace());
        }

        return $this->collection->first();
    }

    /**
     * Check if collection has no results
     *
     * @return bool
     */
    public function notExists(): bool
    {
        return $this->collection->isEmpty();
    }

    /**
     * Get results of collection
     *
     * @return T[]|Error
     */
    public function results(): array|Error
    {
        if ($this->collection->isEmpty()) {
            return new Error(messages: ["No results found for $this->entityClass"], stackTrace: debug_backtrace());
        }

        return $this->collection->toArray();
    }

    /**
     * Get single result of collection
     *
     * @return T|Error
     */
    public function single(): object
    {
        if ($this->collection->isEmpty()) {
            return new Error(messages: ["No results found for $this->entityClass"], stackTrace: debug_backtrace());
        } elseif ($this->collection->count() > 1) {
            return new Error(messages: ["Multiple results found for $this->entityClass"], stackTrace: debug_backtrace());
        }

        return $this->collection->first();
    }
}