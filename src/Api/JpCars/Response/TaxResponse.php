<?php

namespace AtpCore\Api\JpCars\Response;

class TaxResponse
{
    /** @var double */
    public $value;
    /** @var string */
    public $type;
    /** @var TaxDataResponse */
    public $data;
    /** @var string */
    public $source;
    /** @var integer */
    public $co2;
    /** @var string */
    public $country;
}
