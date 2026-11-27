<?php

namespace AtpCore\Symfony\Resource;

use Doctrine\Common\Collections\Order;

/**
 * Relation under its own name, optionally filtered and sorted (see ComputedField::relation())
 */
final class RelationField extends ComputedField
{
    private ?\Closure $where = null;
    private array $orderBy = [];
    private bool $first = false;
    private ?\Closure $transform = null;

    public function __construct(
        private readonly string $relation,
        private readonly array $selection,
    ) {}

    /**
     * Condition on the records (paths relative to the target-entity)
     *
     * @param \Closure $build fn (Where $w, <ClientContext of the application, e.g. Session> $c)
     */
    public function where(\Closure $build): static
    {
        $this->where = $build;
        return $this;
    }

    /**
     * Sorting of the records (default id ascending, the database-order of legacy)
     *
     * @param array<string, Order> $orderBy
     */
    public function orderBy(array $orderBy): static
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * Only the first record (or null) instead of a list
     */
    public function first(): static
    {
        $this->first = true;
        return $this;
    }

    /**
     * Transform the value (list, record or null) when the legacy-output can't be expressed as a selection
     *
     * @param \Closure $transform fn (mixed $value, <ClientContext of the application> $c): mixed
     */
    public function transform(\Closure $transform): static
    {
        $this->transform = $transform;
        return $this;
    }

    /**
     * Only whether there is at least one record (bool instead of the records)
     */
    public function exists(): static
    {
        return $this->transform(fn (mixed $records) => !empty($records));
    }

    public function getRelation(): string { return $this->relation; }
    public function getSelection(): array { return $this->selection; }
    public function getWhere(): ?\Closure { return $this->where; }
    /** @return array<string, Order> */
    public function getOrderBy(): array { return $this->orderBy; }
    public function getFirst(): bool { return $this->first; }
    public function getTransform(): ?\Closure { return $this->transform; }
}
