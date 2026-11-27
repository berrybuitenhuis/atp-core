<?php

namespace AtpCore\Symfony\Resource;

/**
 * Collects the named filters (defaultFilter) of a resource
 */
final class Filters
{
    /**
     * @var list<array{string, \Closure, \UnitEnum|list<\UnitEnum>}>
     */
    private array $entries = [];

    /**
     * @param \Closure $build fn (Where $w, <ClientContext of the application, e.g. Session> $c)
     * @param \UnitEnum|list<\UnitEnum> $for
     */
    public function named(string $name, \Closure $build, \UnitEnum|array $for): static
    {
        $this->entries[] = [$name, $build, $for];
        return $this;
    }

    /**
     * Get named filters for client
     *
     * @return array<string, \Closure>
     * @throws DefinitionException
     */
    public function resolve(\UnitEnum $client): array
    {
        $filters = [];
        foreach ($this->entries as [$name, $build, $for]) {
            if (!in_array($client, is_array($for) ? $for : [$for], true)) continue;
            if (isset($filters[$name])) throw new DefinitionException("Duplicate named filter \"$name\" for $client->name");
            $filters[$name] = $build;
        }

        // Return
        return $filters;
    }
}
