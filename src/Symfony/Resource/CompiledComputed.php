<?php

namespace AtpCore\Symfony\Resource;

use Doctrine\Common\Collections\Order;

/**
 * Validated computed field
 */
final readonly class CompiledComputed
{
    public const CALC = 'calc';
    public const RELATION = 'relation';
    public const RESOLVE = 'resolve';

    /**
     * @param list<string> $dependencies calc: paths relative to the entity of the selection-level
     * @param array<string, Order> $orderBy relation: sorting of the records
     */
    public function __construct(
        public string $type,
        public string $name,
        public ?\Closure $calculate = null,
        public array $dependencies = [],
        public ?string $relation = null,
        public ?ExposureNode $child = null,
        public ?\Closure $where = null,
        public array $orderBy = [],
        public bool $first = false,
        public ?\Closure $transform = null,
        public ?\Closure $resolver = null,
    ) {}
}
