<?php

namespace AtpCore\Api\Autodata\Response;

class DamageParts
{
    /** @var DamageItem[] */
    public $items;
    /** @var integer */
    public $totalDamage;
    /** @var string */
    public $vatMargin;
    /** @var string|null */
    public $damageComments;
}