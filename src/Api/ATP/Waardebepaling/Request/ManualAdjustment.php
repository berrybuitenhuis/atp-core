<?php

namespace AtpCore\Api\ATP\Waardebepaling\Request;

class ManualAdjustment extends BaseRequest
{
    public string $code;
    public float $amount;
    public ?string $reason = null;
}
