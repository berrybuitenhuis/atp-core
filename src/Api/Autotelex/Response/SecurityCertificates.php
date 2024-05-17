<?php

namespace AtpCore\Api\Autotelex\Response;

class SecurityCertificates
{
    /** @var integer|null */
    public $requestCertificateCount;
    /** @var string|null */
    public $requestDate;
    /** @var SecurityCertificate[]|null */
    public $securityCertificates;
    /** @var Status */
    public $status;
}