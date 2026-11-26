<?php

namespace AtpCore\Api\PostNL\Response;

class AddressResult
{
    /** @var Address */
    public $address;
    /** @var boolean */
    public $matched;
    /** @var array|null */
    public $availableHouseNumberSuffixes;
}