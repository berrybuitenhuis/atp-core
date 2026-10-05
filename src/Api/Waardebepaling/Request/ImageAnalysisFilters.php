<?php

namespace AtpCore\Api\Waardebepaling\Request;

class ImageAnalysisFilters extends BaseRequest
{
    public ?float $min_confidence = null;
    public ?float $min_box_confidence = null;
    /** @var string[]|null */
    public ?array $allowed_damage_types = null;
}
