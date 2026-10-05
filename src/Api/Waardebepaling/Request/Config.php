<?php

namespace AtpCore\Api\Waardebepaling\Request;

class Config extends BaseRequest
{
    public ?bool $include_image_analysis = null;
    public ?ImageAnalysisFilters $image_analysis_filters = null;
}
