<?php
namespace AtpCore\Api\UnameIT\Entity;

use AtpCore\Api\Base;

class Lead extends Base
{

    /** @var string */
    public $accountNumber;
    /** @var string */
    public $leadId;
    /** @var int */
    public $companyId;
    /** @var string */
    public $companyName;
    /** @var string */
    public $link;

}