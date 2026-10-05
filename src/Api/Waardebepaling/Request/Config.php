<?php

namespace AtpCore\Api\Waardebepaling\Request;

class Config extends BaseRequest
{
    public ?string $mode = null; // advisory, review, auto
    public ?bool $include_comparables = null;
    public ?bool $include_breakdown = null;
    public ?bool $include_text_analysis = null;
    public ?bool $include_image_analysis = null;
    public ?bool $save_result = null;
    public ?ImageAnalysisFilters $image_analysis_filters = null;
}
