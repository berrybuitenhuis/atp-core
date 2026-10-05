<?php

namespace AtpCore\Api\Waardebepaling\Request;

class Damage extends BaseRequest
{
    public ?string $written_description = null;
    public ?string $customer_comment = null;
    public ?string $technical_comment = null;
    public ?bool $drivable = null;
}
