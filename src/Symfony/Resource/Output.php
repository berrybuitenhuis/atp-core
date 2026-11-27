<?php

namespace AtpCore\Symfony\Resource;

/**
 * Output of a level: which fields, relations and computed fields it shows, in this order
 *
 *     Output::of('id', 'firstName', 'lastName')
 *         ->alias('firstname', 'firstName')
 *         ->relation('SalutationType', Output::of('id', 'name'))
 *         ->computed('Email', $this->personEmail())
 */
final class Output
{
    /**
     * name => true (field), array (relation with its sub-selection) or ComputedField
     */
    private array $selection = [];

    private function __construct() {}

    /**
     * Output starting with these fields of the entity
     */
    public static function of(string $field, string ...$fields): self
    {
        return (new self())->fields($field, ...$fields);
    }

    /**
     * Empty output, to start with relations or computed fields
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Fields of the entity
     */
    public function fields(string ...$names): static
    {
        foreach ($names as $name) {
            $this->selection[$name] = true;
        }
        return $this;
    }

    /**
     * Relation of the entity under its own name, with its sub-selection
     */
    public function relation(string $name, Output $output): static
    {
        $this->selection[$name] = $output->getSelection();
        return $this;
    }

    /**
     * Same value under another name: a field (path, may go through to-one relations), or with an output a relation of the entity
     */
    public function alias(string $name, string $path, ?Output $output = null): static
    {
        return $this->computed($name, ($output === null) ? ComputedField::alias($path) : ComputedField::relation($path, $output));
    }

    /**
     * Fixed value for every record (e.g. a legacy-field that is always true or null)
     */
    public function value(string $name, mixed $value): static
    {
        return $this->computed($name, ComputedField::value($value));
    }

    /**
     * Calculated from fields of the same record; the callable gets their values in order, plus the ClientContext as last argument
     */
    public function calc(string $name, string|array $fields, callable $calculate): static
    {
        return $this->computed($name, ComputedField::calc($fields, $calculate));
    }

    /**
     * Computed field under a name (relation, resolver, or a field of a resolver-class)
     */
    public function computed(string $name, ComputedField $field): static
    {
        $this->selection[$name] = $field;
        return $this;
    }

    /**
     * @return array<string, true|array|ComputedField>
     */
    public function getSelection(): array
    {
        return $this->selection;
    }
}
