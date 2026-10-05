<?php

namespace AtpCore\Api\Waardebepaling\Request;

class Vehicle extends BaseRequest
{
    public int $mileage;
    public ?string $mileage_type = null;
    public ?string $registration = null;
    public ?string $make = null;
    public ?string $model = null;
    public ?string $description = null;
    public ?string $specific = null;
    public ?string $type = null;
    public ?string $type_commercial = null;
    public ?int $build_year = null;
    public ?string $date_first_admission = null;
    public ?string $date_last_registration = null;
    public ?string $apk_expires_at = null;
    public ?string $fuel = null;
    public ?string $transmission = null;
    public ?string $body_type = null;
    public ?string $drive_type = null;
    public ?int $power_kw = null;
    public ?int $power_hp = null;
    public ?int $power_system_kw = null;
    public ?int $power_system_hp = null;
    public ?int $cylinder_volume = null;
    public ?int $cylinders = null;
    public ?int $doors = null;
    public ?int $seats = null;
    public ?int $gears = null;
    public ?string $color = null;
    public ?string $interior_color = null;
    public ?string $lining = null;
    public ?string $lacquer = null;
    public ?int $new_price = null;
    public ?int $bpm_amount = null;
    public ?int $rest_bpm = null;
    public ?int $rest_bpm_export = null;
    public ?string $vat_margin = null;
    public ?bool $is_import = null;
    /** @var string[]|null */
    public ?array $options = null;
}
