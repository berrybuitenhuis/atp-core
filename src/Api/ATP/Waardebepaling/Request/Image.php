<?php

namespace AtpCore\Api\ATP\Waardebepaling\Request;

class Image extends BaseRequest
{
    public string $url;
    public ?string $position = null;
}
