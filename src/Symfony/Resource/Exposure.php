<?php

namespace AtpCore\Symfony\Resource;

use AtpCore\Symfony\Context\Clients;

/**
 * Collects the exposure-declarations of a resource
 *
 * Every add() is a Output; declarations for the same client are deep-merged
 */
final class Exposure
{
    /**
     * @var list<array{array, \UnitEnum|Clients|list<\UnitEnum>}>
     */
    private array $entries = [];

    /**
     * @param \UnitEnum|Clients|list<\UnitEnum> $for
     */
    public function add(Output $output, \UnitEnum|Clients|array $for): static
    {
        $this->entries[] = [$output->getSelection(), $for];
        return $this;
    }

    /**
     * Get merged selection for client
     */
    public function resolve(\UnitEnum $client): array
    {
        $selection = [];
        foreach ($this->entries as [$entry, $for]) {
            if (Clients::match($for, $client)) {
                $selection = self::merge($selection, $entry);
            }
        }

        // Return
        return $selection;
    }

    /**
     * Deep-merge two selections, keeping declaration-order
     */
    private static function merge(array $base, array $addition): array
    {
        foreach ($addition as $key => $value) {
            if (!is_array($value)) {
                // Field or computed field: a later declaration with the same name replaces the earlier one
                $base[$key] = $value;
            } else {
                $base[$key] = self::merge(is_array($base[$key] ?? null) ? $base[$key] : [], $value);
            }
        }

        // Return
        return $base;
    }
}
