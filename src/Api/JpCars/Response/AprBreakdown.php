<?php

namespace AtpCore\Api\JpCars\Response;

class AprBreakdown
{
    /** @var AprBreakdownElement */
    public $window_size;
    /** @var AprBreakdownElement */
    public $sensitivity;
    /** @var AprBreakdownElement */
    public $mileage_mean;
    /** @var AprBreakdownElement */
    public $etr;
    /** @var AprBreakdownElement */
    public $own_supply_window_ratio;
    /** @var AprBreakdownElement */
    public $window_unlocked;
}