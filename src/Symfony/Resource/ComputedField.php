<?php

namespace AtpCore\Symfony\Resource;

use AtpCore\Symfony\Context\Clients;

/**
 * Computed field in a selection, added with Output::computed(name, field); Output::alias(), value() and calc() are shortcuts
 *
 * | Factory                                         | Meaning                                                     | Options                                                   |
 * |-------------------------------------------------|-------------------------------------------------------------|-----------------------------------------------------------|
 * | ComputedField::alias('firstName')               | same value under another name                               | for()                                                     |
 * | ComputedField::value(true)                      | fixed value (also null)                                     | for()                                                     |
 * | ComputedField::calc(['a', 'B.c'], fn)           | calculated from fields of the same record (+ ClientContext) | for()                                                     |
 * | ComputedField::relation('Rel', Output::of(...)) | a relation under its own name, optionally filtered/sorted   | for(), where(), orderBy(), first(), transform(), exists() |
 * | ComputedField::resolve($r->method(...))         | value from a resolver (query or service), per batch of ids  | for()                                                     |
 *
 * Output: the key is always present; an empty list becomes null (legacy conv-behaviour)
 */
abstract class ComputedField
{
    private \UnitEnum|Clients|array|null $for = null;

    /**
     * Same value as another field (path, may go through to-one relations), under another name
     */
    public static function alias(string $path): CalculatedField
    {
        return new CalculatedField(fn ($value) => $value, [$path]);
    }

    /**
     * Fixed value for every record (e.g. a legacy-field that is always true or null)
     */
    public static function value(mixed $value): CalculatedField
    {
        return new CalculatedField(fn () => $value, []);
    }

    /**
     * Calculated from fields of the same record; the callable gets their values in order, plus the ClientContext as last argument
     *
     * @param string|list<string> $fields paths relative to the entity of the selection-level (may go through to-one relations)
     */
    public static function calc(string|array $fields, callable $calculate): CalculatedField
    {
        return new CalculatedField($calculate(...), (array) $fields);
    }

    /**
     * Relation of the entity of the selection-level under its own name; a to-one relation gives one record (or null)
     *
     * @param Output $output selection on the target-entity
     */
    public static function relation(string $relation, Output $output): RelationField
    {
        return new RelationField($relation, $output->getSelection());
    }

    /**
     * Value from a resolver: fn (list<int> $ids, ClientContext $context): array<int, mixed> -- one call for all records of a level
     */
    public static function resolve(\Closure $resolver): ResolvedField
    {
        return new ResolvedField($resolver);
    }

    /**
     * Only for these clients (also inside nested selections); other clients don't get the key at all
     *
     * @param \UnitEnum|Clients|list<\UnitEnum> $for
     */
    public function for(\UnitEnum|Clients|array $for): static
    {
        $this->for = $for;
        return $this;
    }

    /**
     * @return \UnitEnum|Clients|list<\UnitEnum>|null
     */
    public function getFor(): \UnitEnum|Clients|array|null
    {
        return $this->for;
    }
}
