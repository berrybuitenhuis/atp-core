<?php

namespace AtpCore\Api\Autodata\Response;

class AutoVin
{
    /** @var string|null */
    public $make;
    /** @var mixed|null */
    public $model;
    /** @var string|null */
    public $trimPackage;
    /** @var string|null */
    public $paintCode;
    /** @var string|null */
    public $paintDescription;
    /** @var string|null */
    public $paintRendering;
    /** @var AutoVinOptions|null */
    public $options;
}