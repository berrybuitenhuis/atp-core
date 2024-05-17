<?php

namespace AtpCore\Api\Autotelex\Response;

class ValueData
{
    /** @var string */
    public $datumTaxatie;
    /** @var string|null */
    public $jato;
    /** @var ResidualValueData[]|null */
    public $restwaarden;
    /** @var Status */
    public $status;
    /** @var integer */
    public $version;
}