<?php

namespace AtpCore\Api\JpCars\Response;

class TaxDataResponse
{
    /** @var double */
    public $discount;
    /** @var integer */
    public $value_init;
    /** @var integer|null */
    public $price_catalog_average;
}
