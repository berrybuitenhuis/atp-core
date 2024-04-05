<?php

namespace AtpCore\Api\JpCars\Request;

class ValuateRequest
{
    public string $reference;
    public int $mileage;
    public string $specials;
    public string $equipment;
    public string $make;
    public string $model;
    public string $body;
    public string $fuel;
    public string $gear;
    public int $build;
    public string $build_date;
    public int $hp;
    public int $four_doors;
    public int $co2;
    public int $maturity_months;
    public int $mileage_at_maturity;
}