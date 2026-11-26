<?php

namespace AtpCore\Api\ATP\Waardebepaling\Request;

class Context extends BaseRequest
{
    public ?string $country = null;
    public ?string $origin_channel = null;
    public ?string $valuation_type = null;
    public ?string $application = null;
    public ?string $external_company_id = null;
}
