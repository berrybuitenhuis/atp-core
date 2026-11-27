<?php

namespace AtpCore\Symfony\Resource;

/**
 * Builder for conditions (combined with AND); paths are property-names in dot-notation
 */
final class Where
{
    /**
     * @var list<Condition>
     */
    private array $conditions = [];

    public function eq(string $path, mixed $value): static { return $this->add('eq', $path, $value); }
    public function neq(string $path, mixed $value): static { return $this->add('neq', $path, $value); }
    public function like(string $path, mixed $value): static { return $this->add('like', $path, $value); }
    public function lt(string $path, mixed $value): static { return $this->add('lt', $path, $value); }
    public function lte(string $path, mixed $value): static { return $this->add('lte', $path, $value); }
    public function gt(string $path, mixed $value): static { return $this->add('gt', $path, $value); }
    public function gte(string $path, mixed $value): static { return $this->add('gte', $path, $value); }
    public function in(string $path, array $value): static { return $this->add('in', $path, $value); }
    public function notIn(string $path, array $value): static { return $this->add('notIn', $path, $value); }
    public function isNull(string $path): static { return $this->add('isNull', $path); }
    public function isNotNull(string $path): static { return $this->add('isNotNull', $path); }

    /**
     * At least one branch must match; each branch is combined with AND
     *
     * @param \Closure(Where): mixed ...$branches
     */
    public function or(\Closure ...$branches): static
    {
        $wheres = [];
        foreach ($branches as $branch) {
            $branch($where = new Where());
            $wheres[] = $where;
        }
        $this->conditions[] = new Condition('or', value: $wheres);
        return $this;
    }

    /**
     * Add conditions only when the condition is true
     *
     * @param \Closure(Where): mixed $build
     */
    public function when(bool $condition, \Closure $build): static
    {
        if ($condition) $build($this);
        return $this;
    }

    /**
     * A record of a to-many relation exists that matches the criteria
     *
     * @param string $relation to-many association of the current entity
     * @param array<string, mixed> $criteria path (relative to the target-entity) => value (array = IN)
     */
    public function exists(string $relation, array $criteria = []): static
    {
        $this->conditions[] = new Condition('exists', $relation, $criteria);
        return $this;
    }

    /**
     * Raw DQL-condition (escape hatch for hand-written subqueries); use f as root-alias
     */
    public function raw(string $dql, array $parameters = []): static
    {
        $this->conditions[] = new Condition('raw', value: $dql, parameters: $parameters);
        return $this;
    }

    /**
     * @return list<Condition>
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    private function add(string $operator, string $path, mixed $value = null): static
    {
        $this->conditions[] = new Condition($operator, $path, $value);
        return $this;
    }
}
