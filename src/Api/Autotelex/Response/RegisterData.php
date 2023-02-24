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
    /** @var integer */
    public $rolMileage;
    /** @var boolean */
    public $rolMileageNotationInKm;
    /** @var boolean */
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