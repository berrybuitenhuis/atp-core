<?php

namespace AtpCore\Symfony\Resource;

/**
 * Collects the custom filter-aliases of a resource (associations of the root are aliases automatically)
 */
final class Aliases
{
    /**
     * @var array<string, array{string, ?\Closure}>
     */
    private array $aliases = [];

    /**
     * @param string $path path from the root-entity, ending on an association
     * @param ?\Closure(Where): mixed $on join-condition, paths relative to the aliased entity
     * @throws DefinitionException
     */
    public function add(string $alias, string $path, ?\Closure $on = null): static
    {
        if (isset($this->aliases[$alias])) throw new DefinitionException("Duplicate custom alias \"$alias\"");
        $this->aliases[$alias] = [$path, $on];
        return $this;
    }

    /**
     * @return array<string, array{string, ?\Closure}>
     */
    public function all(): array
    {
        return $this->aliases;
    }
}
