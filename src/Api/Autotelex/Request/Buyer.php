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
    public BuyerAddress $mainAddress;
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
        string $rdwIdentificationNumber,
        BuyerAddress $mainAddress)
    {
        $this->chamberOfCommerceNumber = $chamberOfCommerceNumber;
        $this->companyName = $companyName;
        $this->emailAddress = $emailAddress;
        $this->firstName = $firstName;
        $this->infix = $infix;
        $this->lastName = $lastName;
        $this->mainAddress = $mainAddress;
        $this->mobileNumber = $mobileNumber;
        $this->phoneNumber = $phoneNumber;
        $this->rdwIdentificationNumber = $rdwIdentificationNumber;
    }
}