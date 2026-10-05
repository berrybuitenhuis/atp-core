<?php

namespace AtpCore\Api\Waardebepaling\Request;

class Rules extends BaseRequest
{
    public ?bool $apply_default_rules = null;
    /** @var string[]|null */
    public ?array $disable_rules = null;
    /** @var ManualAdjustment[]|null */
    public ?array $manual_adjustments = null;
}
