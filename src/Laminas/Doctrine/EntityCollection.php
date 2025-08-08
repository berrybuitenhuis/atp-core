<?php

namespace AtpCore\Laminas\Doctrine;

use AtpCore\Error;

/**
 * @template T of object
 */
class EntityCollection  {
    protected $collection;

    public function __construct(
        protected readonly string $entityClass,
        protected readonly ?array $results = [],
    ) {
        $this->collection = new \Doctrine\Common\Collections\ArrayCollection($results);
    }

    /**
     * Check if collection has results
     *
     * @return bool
     */
    public function exists()
    {
        // Return
        return $this->collection->isEmpty() === false;
    }

    /**
     * Get first result of collection
     *
     * @return T|Error
     */
    public function first()
    {
        // Check for error or empty collection
        if ($this->collection->isEmpty()) {
            return new Error(messages: ["No results found for $this->entityClass"], stackTrace: debug_backtrace());
        }

        // Return first element
        return $this->collection->first();
    }

    /**
     * Check if collection has no results
     *
     * @return bool
     */
    public function notExists()
    {
        // Return
        return $this->collection->isEmpty();
    }

    /**
     * Get results of collection
     *
     * @return T[]|Error
     */
    public function results()
    {
        // Check for error or empty collection
        if ($this->collection->isEmpty()) {
            return new Error(messages: ["No results found for $this->entityClass"], stackTrace: debug_backtrace());
        }

        // Return collection
        return $this->collection->toArray();
    }

    /**
     * Get single result of collection
     *
     * @return T|Error
     */
    public function single()
    {
        // Check for error or empty collection
        if ($this->collection->isEmpty()) {
            return new Error(messages: ["No results found for $this->entityClass"], stackTrace: debug_backtrace());
        } elseif ($this->collection->count() > 1) {
            return new Error(messages: ["Multiple results found for $this->entityClass"], stackTrace: debug_backtrace());
        }

        // Return first element
        return $this->collection->first();
    }
}
