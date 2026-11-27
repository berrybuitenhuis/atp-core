<?php

namespace AtpCore\Symfony\Context;

/**
 * Open set of clients (all, or all except some); only allowed for exposure
 */
final readonly class Clients
{
    /**
     * @param list<\UnitEnum> $excluded
     */
    private function __construct(
        private array $excluded,
    ) {}

    public static function all(): self
    {
        return new self([]);
    }

    public static function except(\UnitEnum ...$clients): self
    {
        return new self(array_values($clients));
    }

    public function contains(\UnitEnum $client): bool
    {
        return !in_array($client, $this->excluded, true);
    }

    /**
     * Check if a for-argument (client, list of clients or Clients) applies to client
     *
     * @param \UnitEnum|Clients|list<\UnitEnum> $for
     */
    public static function match(\UnitEnum|Clients|array $for, \UnitEnum $client): bool
    {
        if ($for instanceof Clients) return $for->contains($client);
        if ($for instanceof \UnitEnum) return $for === $client;
        return in_array($client, $for, true);
    }
}
