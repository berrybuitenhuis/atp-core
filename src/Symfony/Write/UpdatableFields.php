<?php

namespace AtpCore\Symfony\Write;

use AtpCore\Symfony\Context\ClientContext;
use AtpCore\Symfony\Resource\ResourceRegistry;

/**
 * Update-whitelist of an entity for the current client (from the writes() of its resource)
 */
final class UpdatableFields
{
    public function __construct(
        private readonly ResourceRegistry $registry,
        private readonly ClientContext $context,
    ) {}

    /**
     * @return ?list<string> null = no whitelist (entity without resource, or writes() not overridden)
     */
    public function forEntity(string $entity): ?array
    {
        $resourceClass = $this->registry->forEntity($entity);
        if ($resourceClass === null) return null;

        // Return
        return $this->registry->get($resourceClass, $this->context)->updatable;
    }
}
