<?php

namespace AtpCore\Symfony\Resource;

/**
 * Field calculated from fields of the same record (see ComputedField::alias() and ComputedField::calc())
 */
final class CalculatedField extends ComputedField
{
    /**
     * @param list<string> $dependencies
     */
    public function __construct(
        private readonly \Closure $calculate,
        private readonly array $dependencies,
    ) {}

    public function getCalculate(): \Closure
    {
        return $this->calculate;
    }

    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
