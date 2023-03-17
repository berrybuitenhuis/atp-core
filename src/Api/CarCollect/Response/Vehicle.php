<?php

namespace AtpCore\Api\CarCollect\Response;

class Vehicle
{
    /** @var string */
    public $id;
    /** @var string */
    public $brand;
    /** @var string */
    public $model;
    /** @var string */
    public $version;
    /** @var string */
    public $license_plate;
    /** @var integer|null */
    public $award_amount;
    /** @var string|null */
    public $award_type;
    /** @var integer|null */
    public $book_value;
    /** @var integer */
    public $build_year;
    /** @var string|null */
    public $destination;
    /** @var string */
    public $fuel;
    /** @var integer */
    public $mileage_exact;
    /** @var integer|null */
    public $mileage_expected;
    /** @var integer */
    public $mileage;
    /** @var integer */
    public $nap_check;
    /** @var integer */
    public $power;
    /** @var string */
    public $sorting_date;
    /** @var string */
    public $status;
    /** @var string */
    public $tagline;
    /** @var integer|null */
    public $trade_value_average;
    /** @var string */
    public $transmission;
    /** @var boolean */
    public $vat_vehicle;
    /** @var string */
    public $vehicle_type;
    /** @var string */
    public $vin_number;
    /** @var string[]|null */
    public $supply_countries;
    /** @var string[]|null */
    public $demand_countries;
    /** @var string */
    public $addition_rate_valid_until;
    /** @var integer */
    public $addition_rate;
    /** @var integer */
    public $co2_emission;
    /** @var string */
    public $energy_label;
    /** @var string */
    public $intake_date;
    /** @var string|null */
    public $intake_date_expected;
    /** @var integer|null */
    public $number_of_keys;
    /** @var string */
    public $rdw_euro_class;
    /** @var integer */
    public $rdw_max_mass_restrained;
    /** @var integer */
    public $rdw_max_mass_unrestrained;
    /** @var integer */
    public $rdw_max_mass;
    /** @var integer|null */
    public $rdw_payload;
    /** @var string */
    public $steering_wheel_side;
    /** @var Exterior */
    public $exterior;
    /** @var Interior */
    public $interior;
    /** @var Other */
    public $other;
    /** @var Technical */
    public $technical;
    /** @var Wheels */
    public $wheels;
    /** @var Damage[] */
    public $damages;
    /** @var Document[]|null */
    public $documents;
    /** @var Image[] */
    public $images;
}