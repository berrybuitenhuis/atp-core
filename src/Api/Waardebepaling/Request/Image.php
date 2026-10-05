<?php

namespace AtpCore\Api\Waardebepaling\Request;

class Image extends BaseRequest
{
    public string $url;
    public ?string $position = null;
}
