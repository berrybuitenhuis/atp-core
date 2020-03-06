<?php
namespace AtpCore\Api\UnameIT\Entity;

use AtpCore\Api\Base;

class Address extends Base
{

    /** @var string */
    public $street;
    /** @var int */
    public $number;
    /** @var string */
    public $numberSuffix;
    /** @var string */
    public $zipCode;
    /** @var string */
    public $city;
    /** @var string */
    public $country;

}