<?php

namespace AtpCore\Api\JpCars\Response;

class ValuateResponse
{
    /** @var string */
    public $license_plate;
    /** @var integer|null */
    public $value;
    /** @var integer|null */
    public $value_exex;
    /** @var integer|null */
    public $topdown_value;
    /** @var TopDownValueBreakdown|null */
    public $topdown_value_breakdown;
    /** @var double|null */
    public $topdown_value_at_maturity;
    /** @var TopDownValueBreakdown|null */
    public $topdown_value_at_maturity_breakdown;
    /** @var double|null */
    public $value_at_maturity;
    /** @var integer|null */
    public $stat_turnover_ext;
    /** @var integer|null */
    public $stat_turnover_int;
    /** @var integer|null */
    public $window_size;
    /** @var double|null */
    public $price_sensitivity;
    /** @var double|null */
    public $mileage_mean;
    /** @var string */
    public $window_url;
    /** @var string|null */
    public $specials_info;
    /** @var integer|null */
    public $apr;
    /** @var AprBreakdown|null */
    public $apr_breakdown;
    /** @var string|null */
    public $country;
    /** @var integer|null */
    public $own_supply_window_count;
    /** @var string|null */
    public $options;
    /** @var TaxResponse[]|null */
    public $taxes;
    /** @var string|null */
    public $error;
    /** @var string|null */
    public $error_message;
}
