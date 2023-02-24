<?php

namespace AtpCore\Api\Autotelex\Response;

class VehicleType
{
    /** @var string */
    public $brand;
    /** @var string|null */
    public $fotoURLFront;
    /** @var string|null */
    public $fotoURLInterior;
    /** @var string|null */
    public $fotoURLRear;
    /** @var integer|null */
    public $geleverdTotJaar;
    /** @var integer|null */
    public $geleverdVanJaar;
    /** @var integer */
    public $id;
    /** @var integer|null */
    public $licenseplateLinkProbability;
    /** @var string */
    public $model;
    /** @var string|null */
    public $nieuwPrijs;
    /** @var string */
    public $type;
    /** @var integer|null */
    public $vermogen;
    /** @var string|null */
    public $versnelling;
}