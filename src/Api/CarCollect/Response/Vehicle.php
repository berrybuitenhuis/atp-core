<?php

namespace AtpCore\Api\CarCollect\Response;

class Vehicle
{
    /** @var integer|null */
    public $addition_rate;
    /** @var string|null */
    public $addition_rate_valid_until;
    /** @var integer|null */
    public $award_amount;
    /** @var string|null */
    public $award_type;
    /** @var string|null */
    public $bid_indication;
    /** @var integer|null */
    public $book_value;
    /** @var string */
    public $brand;
    /** @var integer */
    public $build_year;
    /** @var integer|null */
    public $buy_now_price;
    /** @var integer|null */
    public $co2_emission;
    /** @var Company */
    public $company;
    /** @var string */
    public $createdAt;
    /** @var string */
    public $currency;
    /** @var Damage[]|null */
    public $damages;
    /** @var string[]|null */
    public $demand_countries;
    /** @var string|null */
    public $destination;
    /** @var Document[]|null */
    public $documents;
    /** @var string|null */
    public $energy_label;
    /** @var Exterior */
    public $exterior;
    /** @var string|null */
    public $expiration_date;
    /** @var string */
    public $fuel;
    /** @var string */
    public $id;
    /** @var Image[] */
    public $images;
    /** @var string|null */
    public $intake_date;
    /** @var string|null */
    public $intake_date_expected;
    /** @var Interior */
    public $interior;
    /** @var string */
    public $license_plate;
    /** @var integer */
    public $mileage;
    /** @var integer|null */
    public $mileage_exact;
    /** @var integer|null */
    public $mileage_expected;
    /** @var string */
    public $model;
    /** @var integer|null */
    public $nap_check;
    /** @var integer|null */
    public $number_of_keys;
    /** @var Other */
    public $other;
    /** @var integer */
    public $power;
    /** @var string|null */
    public $rdw_euro_class;
    /** @var RdwHistory[]|null */
    public $rdw_history;
    /** @var integer|null */
    public $rdw_max_mass;
    /** @var integer|null */
    public $rdw_max_mass_restrained;
    /** @var integer|null */
    public $rdw_max_mass_unrestrained;
    /** @var integer|null */
    public $rdw_payload;
    /** @var string */
    public $registration_country;
    /** @var string */
    public $sales_type;
    /** @var string */
    public $sorting_date;
    /** @var string */
    public $status;
    /** @var string|null */
    public $steering_wheel_side;
    /** @var string[]|null */
    public $supply_countries;
    /** @var string|null */
    public $tagline;
    /** @var Technical */
    public $technical;
    /** @var integer|null */
    public $trade_value_average;
    /** @var string */
    public $trading_expiration_date;
    /** @var string */
    public $transmission;
    /** @var string|null */
    public $transport_scheduled_at;
    /** @var boolean */
    public $vat_vehicle;
    /** @var string */
    public $vehicle_type;
    /** @var string */
    public $version;
    /** @var string|null */
    public $vin_number;
    /** @var Wheels */
    public $wheels;
}