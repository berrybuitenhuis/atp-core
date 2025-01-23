<?php

namespace AtpCore\Api\Autotelex\Request;

class Buyer
{
    public string $chamberOfCommerceNumber;
    public string $companyName;
    public string $emailAddress;
    public string $firstName;
    public string $infix;
    public string $lastName;
    public string $phoneNumber;
    public string $mobileNumber;
    public string $rdwIdentificationNumber;

    public function __construct(
        string $chamberOfCommerceNumber,
        string $companyName,
        string $emailAddress,
        string $firstName,
        string $infix,
        string $lastName,
        string $phoneNumber,
        string $mobileNumber,
        string $rdwIdentificationNumber)
    {
        $this->chamberOfCommerceNumber = $chamberOfCommerceNumber;
        $this->companyName = $companyName;
        $this->emailAddress = $emailAddress;
        $this->firstName = $firstName;
        $this->infix = $infix;
        $this->lastName = $lastName;
        $this->phoneNumber = $phoneNumber;
        $this->mobileNumber = $mobileNumber;
        $this->rdwIdentificationNumber = $rdwIdentificationNumber;
    }
}