<?php

namespace AtpCore\Api\CarCollect\Response;

class Damage
{
    /** @var string */
    public $createdAt;
    /** @var string|null */
    public $description;
    /** @var string */
    public $id;
    /** @var string */
    public $location;
    /** @var integer|null */
    public $recovery_costs;
    /** @var string|null */
    public $solution;
    /** @var string */
    public $type;
    /** @var boolean */
    public $visible_for_trader;
    /** @var Image[]|null */
    public $images;
}