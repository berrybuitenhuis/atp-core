<?php

namespace AtpCore\Api\Autotelex\Response;

class SecurityCertificate
{
    /** @var string */
    public $approvalNumber;
    /** @var string */
    public $certificateNumber;
    /** @var boolean|null */
    public $certificateValid;
    /** @var string|null */
    public $certificateValidDate;
    /** @var string */
    public $classCode;
    /** @var string */
    public $classDescription;
    /** @var boolean */
    public $fromFactory;
}