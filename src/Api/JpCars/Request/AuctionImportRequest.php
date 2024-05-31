<?php

namespace AtpCore\Api\JpCars\Request;

class AuctionImportRequest
{
    public int $build;
    public int $co2;
    public int $four_doors;
    public int $hp;
    public int $maturity_months;
    public int $mileage;
    public int $mileage_at_maturity;
    public string $body;
    public string $build_date;
    public string $end_time;
    public string $equipment;
    public string $fuel;
    public ?string $gear;
    public string $image;
    public string $make;
    public string $model;
    public string $license_plate;
    public string $reference;
    public string $specials_info;
    public string $url;
}