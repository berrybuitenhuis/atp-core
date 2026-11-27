<?php

namespace AtpCore\Symfony\Resource;

use Doctrine\Common\Collections\Order;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Definition of an API-resource for one entity (replaces Options + conv-functions)
 */
#[AutoconfigureTag('atp.resource')]
interface Resource
{
    /**
     * Filter-aliases with another name than the property or with a join-condition
     */
    public function customAliases(Aliases $a): void;

    /**
     * Default sorting when the client sends no orderBy
     *
     * @return array<string, Order>
     */
    public function defaultOrder(\UnitEnum $client): array;

    /**
     * @return class-string
     */
    public function entity(): string;

    /**
     * What a client sees (was: getDataFields)
     */
    public function expose(Exposure $e): void;

    /**
     * Named filters (was: getDefaultFilterOptions + getDefaultFilter)
     */
    public function filters(Filters $f): void;

    /**
     * Which rows a client sees (was: getClientFilter): Scope::where(...), Scope::unrestricted() or Scope::denied()
     */
    public function scope(\UnitEnum $client): Scope;

    /**
     * What a client may update (was: verifyDataFields)
     */
    public function writes(Writes $w): void;
}
