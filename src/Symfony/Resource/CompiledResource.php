<?php

namespace AtpCore\Symfony\Resource;

use Doctrine\Common\Collections\Order;

/**
 * Validated definition of a resource for one client
 */
final readonly class CompiledResource
{
    /**
     * @param class-string $entity
     * @param ?list<\Closure> $scopes null = client not allowed
     * @param array<string, AliasJoin> $aliases automatic (associations of the root) plus custom
     * @param array<string, \Closure> $filters
     * @param array<string, Order> $defaultOrder
     * @param ?list<string> $updatable null = no whitelist
     */
    public function __construct(
        public string $entity,
        public ExposureNode $exposure,
        public ?array $scopes,
        public array $aliases,
        public array $filters,
        public array $defaultOrder,
        public ?array $updatable,
    ) {}

    public function getAllowed(): bool
    {
        return $this->scopes !== null;
    }
}
