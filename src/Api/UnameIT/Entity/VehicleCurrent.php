<?php
namespace AtpCore\Api\UnameIT\Entity;

use AtpCore\Api\Base;

class VehicleCurrent extends Base
{

    /** @var string */
    public $fuelType;
    /** @var string */
    public $make;
    /** @var string */
    public $model;
    /** @var string */
    public $registration;
    /** @var string */
    public $type;
    /** @var int */
    public $year;

}