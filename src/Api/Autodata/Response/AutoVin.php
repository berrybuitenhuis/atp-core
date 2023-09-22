<?php

namespace AtpCore\Api\Autodata\Response;

class AutoVin
{
    /** @var string */
    public $make;
    /** @var string */
    public $model;
    /** @var string */
    public $trimPackage;
    /** @var string */
    public $paintCode;
    /** @var string */
    public $paintDescription;
    /** @var string */
    public $paintRendering;
    /** @var AutoVinOptions */
    public $options;
}