<?php

namespace AtpCore\Api\Waardebepaling\Request;

class Callback extends BaseRequest
{
    public string $url;
    public ?string $secret = null;
}
