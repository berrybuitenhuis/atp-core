<?php
namespace AtpCore\Api\UnameIT\Entity;

use AtpCore\Api\Base;

class Relation extends Base
{

    /** @var string */
    public $relationType;
    /** @var string */
    public $emailAddress;
    /** @var array */
    public $phoneNumbers;
    /** @var array */
    public $addresses;

    /** @var string */
    public $salutation;
    /** @var string */
    public $initials;
    /** @var string */
    public $firstname;
    /** @var string */
    public $infix;
    /** @var string */
    public $lastname;
    /** @var date */
    public $dateOfBirth;
    /** @var string */
    public $gender;

    /** @var string */
    public $companyName;
    /** @var string */
    public $cocNumber;

}