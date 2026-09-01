<?php

namespace AtpCore\Api\Autodata\Response;

class AutoVin
{
    /** @var string */
    public $make;
    /** @var mixed */
    public $model;
    /** @var string */
    public $trimPackage;
    /** @var string */
    public $paintCode;
    /** @var string */
    public $paintDescription;
    /** @var string */
    public $paintRendering;
    /** @var AutoVinOptions|null */
    public $options;
}