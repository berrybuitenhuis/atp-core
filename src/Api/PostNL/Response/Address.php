<?php

namespace AtpCore\Api\PostNL\Response;

class Address
{
    /** @var string */
    public $street;
    /** @var string */
    public $postalCode;
    /** @var string */
    public $houseNumber;
    /** @var string|null */
    public $houseNumberSuffix;
    /** @var string */
    public $city;
}