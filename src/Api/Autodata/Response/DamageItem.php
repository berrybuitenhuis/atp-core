<?php

namespace AtpCore\Api\Autodata\Response;

class DamageItem
{
    /** @var string|null */
    public $type;
    /** @var string|null */
    public $repair;
    /** @var string */
    public $part;
    /** @var string|null */
    public $price;
    /** @var string[]|null */
    public $damagePhotos;
}