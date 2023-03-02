<?php

namespace AtpCore\Api\Autotelex\Response;

class Packet
{
    /** @var string */
    public $id;
    /** @var string|null */
    public $manufacturerCode;
    /** @var string|null */
    public $manufacturerName;
    /** @var string */
    public $name;
    /** @var Options|null */
    public $opties;
    /** @var integer */
    public $price;
    /** @var boolean */
    public $selected;
    /** @var boolean */
    public $valueAddingOption;
}