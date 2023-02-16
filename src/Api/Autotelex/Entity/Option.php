<?php

namespace AtpCore\Api\Autotelex\Entity;

class Option
{
    public $id;
    /** @var ManufacturerOptionCodes */
    public $manufacturerOptionCodes;
    public $name;
    public $price;
    public $selected;
    public $valueAddingOption;
}