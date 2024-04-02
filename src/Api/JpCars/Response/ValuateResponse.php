<?php

namespace AtpCore\Api\JpCars\Response;

class ValuateResponse
{
    /** @var string */
    public $license_plate;
    /** @var integer */
    public $value;
    /** @var integer */
    public $value_exex;
    /** @var integer */
    public $stat_turnover_ext;
    /** @var integer */
    public $stat_turnover_int;
    /** @var integer */
    public $window_size;
    /** @var double */
    public $price_sensitivity;
    /** @var double */
    public $mileage_mean;
    /** @var string */
    public $window_url;
    /** @var string */
    public $specials_info;
    /** @var string */
    public $country;
    /** @var integer */
    public $own_supply_window_count;
    /** @var TaxResponse[] */
    public $taxes;
}
