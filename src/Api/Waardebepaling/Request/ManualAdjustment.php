<?php

namespace AtpCore\Api\Waardebepaling\Request;

class ManualAdjustment extends BaseRequest
{
    public string $code;
    public float $amount;
    public ?string $reason = null;
}
