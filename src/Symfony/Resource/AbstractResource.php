<?php

namespace AtpCore\Symfony\Resource;

use Doctrine\Common\Collections\Order;

/**
 * Defaults for the optional parts of a resource (equal to legacy)
 */
abstract class AbstractResource implements Resource
{
    public function filters(Filters $f): void {}

    // Legacy: without verifyDataFields an update accepts every field
    public function writes(Writes $w): void
    {
        $w->withoutWhitelist();
    }

    public function customAliases(Aliases $a): void {}

    public function defaultOrder(\UnitEnum $client): array
    {
        return ['id' => Order::Ascending];
    }
}
