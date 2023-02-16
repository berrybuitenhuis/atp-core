<?php

namespace AtpCore\Api\Autotelex\Entity;

class Packet
{
    public $id;
    public $manufacturerCode;
    public $manufacturerName;
    public $name;
    /** @var Options */
    public $opties;
    public $price;
    public $selected;
    public $valueAddingOption;
}