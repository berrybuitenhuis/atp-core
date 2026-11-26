<?php

namespace AtpCore\Api\ATP\Waardebepaling\Request;

class Callback extends BaseRequest
{
    public string $url;
    public ?string $secret = null;
}
