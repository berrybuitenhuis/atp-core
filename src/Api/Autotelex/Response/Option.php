<?php

namespace AtpCore\Api\Autotelex\Response;

class Option
{
    /** @var string */
    public $id;
    /** @var ManufacturerOptionCodes|null */
    public $manufacturerOptionCodes;
    /** @var string */
    public $name;
    /** @var integer */
    public $price;
    /** @var boolean */
    public $selected;
    /** @var boolean */
    public $valueAddingOption;
}