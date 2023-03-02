<?php

namespace AtpCore\Api\Autotelex\Response;

class RegisterData
{
    /** @var boolean|null */
    public $aanmeldenSuccess;
    /** @var boolean */
    public $automaticAanmeldenEnabled;
    /** @var boolean */
    public $automaticB2BRoLEnabled;
    /** @var integer|null */
    public $rolMileage;
    /** @var boolean|null */
    public $rolMileageNotationInKm;
    /** @var boolean|null */
    public $rollableForTransport;
    /** @var string|null */
    public $temporaryDocumentNumber;
    /** @var string|null */
    public $temporaryRegistrationCode;
    /** @var boolean */
    public $transportBySeller;
    /** @var string */
    public $vin;
}