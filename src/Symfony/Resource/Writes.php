<?php

namespace AtpCore\Symfony\Resource;

/**
 * Collects the write-declarations (update-whitelist) of a resource
 */
final class Writes
{
    private bool $withoutWhitelist = false;

    /**
     * @var list<array{list<string>, \UnitEnum|list<\UnitEnum>}>
     */
    private array $entries = [];

    /**
     * @param list<string> $fields
     * @param \UnitEnum|list<\UnitEnum> $for
     */
    public function updatable(array $fields, \UnitEnum|array $for): static
    {
        $this->entries[] = [$fields, $for];
        return $this;
    }

    /**
     * No update-whitelist for any client (legacy without verifyDataFields)
     */
    public function withoutWhitelist(): static
    {
        $this->withoutWhitelist = true;
        return $this;
    }

    /**
     * Get updatable fields for client, null if there is no whitelist
     *
     * @return ?list<string>
     * @throws DefinitionException
     */
    public function resolve(\UnitEnum $client): ?array
    {
        if ($this->withoutWhitelist) {
            if (!empty($this->entries)) throw new DefinitionException('withoutWhitelist() cannot be combined with updatable()');
            return null;
        }

        $fields = [];
        foreach ($this->entries as [$entry, $for]) {
            if (in_array($client, is_array($for) ? $for : [$for], true)) {
                $fields = array_merge($fields, $entry);
            }
        }

        // Return
        return array_values(array_unique($fields));
    }
}
