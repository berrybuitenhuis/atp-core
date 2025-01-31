<?php

namespace AtpCore\Api\Autotelex\Request;

class BuyerAddress
{
    public string $city;
    public string $country;
    public string $emailAddress;
    public string $houseNumber;
    public string $phoneNumber;
    public string $postalCode;
    public string $state;
    public string $street;
    public string $website;

    public function __construct(
        string $city,
        string $country,
        string $emailAddress,
        string $houseNumber,
        string $phoneNumber,
        string $postalCode,
        string $state,
        string $street,
        string $website)
    {
        $this->city = $city;
        $this->country = $country;
        $this->emailAddress = $emailAddress;
        $this->houseNumber = $houseNumber;
        $this->phoneNumber = $phoneNumber;
        $this->postalCode = $postalCode;
        $this->state = $state;
        $this->street = $street;
        $this->website = $website;
    }
}