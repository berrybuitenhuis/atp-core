<?php

namespace AtpCore\Api\Autotelex\Response;

class OptionPackage
{
    /** @var string */
    public $id;
    /** @var string|null */
    public $manufacturerCode;
    /** @var string|null */
    public $manufacturerName;
    /** @var string */
    public $name;
    /** @var Option[]|null */
    public $opties;
    /** @var integer */
    public $price;
    /** @var boolean */
    public $selected;
    /** @var boolean */
    public $valueAddingOption;
}