<?php

namespace AtpCore\Api\ATP\Waardebepaling\Request;

class Condition extends BaseRequest
{
    public ?string $general = null;
    public ?string $body = null;
    public ?string $interior = null;
    public ?string $glass = null;
    public ?string $electric = null;
    public ?string $technical = null;
    public ?string $airco_status = null;
    public ?string $maintenance_history = null;
    public ?string $maintenance_guide = null;
    public ?string $tires = null;
    public ?string $used_as = null;
    public ?bool $smoked = null;
    public ?bool $pets = null;
    public ?int $number_of_keys = null;
}
