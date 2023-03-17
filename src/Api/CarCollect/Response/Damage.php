<?php

namespace AtpCore\Api\CarCollect\Response;

class Damage
{
    /** @var string */
    public $id;
    /** @var string|null */
    public $description;
    /** @var string */
    public $location;
    /** @var integer|null */
    public $recovery_costs;
    /** @var string */
    public $solution;
    /** @var string */
    public $type;
    /** @var boolean */
    public $visible_for_trader;
    /** @var Image[] */
    public $images;
}