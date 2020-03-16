<?php
/**
 * API-information: https://www.uname-it.nl/download/?u=lef
 */
namespace AtpCore\Api\UnameIT;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class LefApi extends BaseClass
{
    private $client;
    private $clientHeaders;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param boolean $debug
     */
    public function __construct($hostname, $username, $password, $debug = false)
    {
        // Set client
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);

        // Reset error-messages
        $this->resetErrors();

        // Set default header for client-requests
        $this->clientHeaders = [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            'Content-Type' => 'application/xml'
        ];
    }

    /**
     * Create LEF-lead
     *
     * @param Entity\Lead $lead
     * @param Entity\Relation $relation
     * @param Entity\VehicleCurrent $vehicleCurrent
     * @param Entity\VehicleInterest $vehicleInterest
     * @return bool
     */
    function createLead(Entity\Lead $lead, Entity\Relation $relation, Entity\VehicleCurrent $vehicleCurrent, Entity\VehicleInterest $vehicleInterest = null)
    {
        $body = $this->generateLead($lead, $relation, $vehicleCurrent, $vehicleInterest);

        // Create lead
        $requestHeader = $this->clientHeaders;
        $requestHeader['Content-Length'] = strlen($body);
        $result = $this->client->post('leads/new', ['headers'=>$requestHeader, 'body'=>$body]);
        $responseCode = $result->getStatusCode();

        // Return
        if ($responseCode == 200) {
            return true;
        } else {
            $this->setErrorData((string) $result->getBody());
            $this->setMessages($responseCode . ": " . $result->getReasonPhrase());
            return false;
        }
    }

    /**
     * Converts fueltype to LEF fueltype
     *
     * @param string $originalValue
     * @return string
     */
    private function convertFuelType($originalValue)
    {
        switch (strtolower($originalValue)) {
            case "benzine":
                $output = 'Benzine';
                break;
            case "diesel":
                $output = 'Diesel';
                break;
            case "cng":
                $output = 'CNG';
                break;
            case "elektriciteit":
            case "elektrisch":
                $output = 'Elektrisch';
                break;
            case "hybride":
                $output = 'Hybride';
                break;
            case "hybride diesel":
                $output = 'HybrideDiesel';
                break;
            case "hybride lpg":
                $output = 'HybrideLPG';
                break;
            case "lpg":
            case "lpg g3":
                $output = 'LPG';
                break;
            case "waterstof":
                $output = 'Waterstof';
                break;
            default:
                $output = 'Overige';
                break;
        }

        return $output;
    }

    /**
     * Generate Lead XML
     *
     * @param Entity\Lead $leadInfo
     * @param Entity\Relation $relation
     * @param Entity\VehicleCurrent $vehicleCurrent
     * @param Entity\VehicleInterest $vehicleInterest
     * @return string
     */
    private function generateLead(Entity\Lead $leadInfo, Entity\Relation $relation, Entity\VehicleCurrent $vehicleCurrent, Entity\VehicleInterest $vehicleInterest = null)
    {
        // Initialize XML-document
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\" ?><Leads xmlns=\"http://www.uname-it.nl/unameit/xsd/LEF/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.uname-it.nl/unameit/xsd/LEF/Leads.xsd\"></Leads>");

        // Set lead-attributes
        $lead = $xml->addChild("Lead");
        $lead->addChild("LeadID", $leadInfo->leadId);
        $lead->addChild("LeadType", "Sales");
        $lead->addChild("SoortLead", "Taxatie");
        if (!empty($leadInfo->accountNumber)) $lead->addChild("AccountNummer", $leadInfo->accountNumber);
        $lead->addChild("Omschrijving", "Gevraagd bod tbv Inruil van " . $vehicleCurrent->make . " " . $vehicleCurrent->model . " " . $vehicleCurrent->registration . ", " . date("d-m-Y"));
        if (!empty($vehicleInterest)) {
            $lead->addChild("LeadBron", "ATP inruilTaxatie met referentie");
        } else {
            $lead->addChild("LeadBron", "ATP inruilTaxatie zonder referentie");
        }

        // Set relation-attributes
        $customer = $lead->addChild("Relatie");
        if (!empty($relation->companyName)) {
            $company = $customer->addChild("Zakelijk");
            $company->addChild("Bedrijfsnaam", $relation->companyName);
            $company->addChild("KvkNummer", $relation->cocNumber);
        } else {
            $consumer = $customer->addChild("Particulier");
            $consumer->addChild("Aanhef", $relation->salutation);
            $consumer->addChild("Voornaam", $relation->firstname);
            $consumer->addChild("Voorletters", $relation->initials);
            $consumer->addChild("Tussenvoegsel", $relation->infix);
            $consumer->addChild("Achternaam", $relation->lastname);
            if(!empty($relation->dateOfBirth)) {
                $consumer->addChild("GeboorteDatum", $relation->dateOfBirth->format("Y-m-d"));
            }
            if (in_array(strtolower($relation->gender), ['man','male','mister'])) {
                $consumer->addChild("Geslacht", "Man");
            } elseif (in_array(strtolower($relation->gender), ['vrouw','female','missis'])) {
                $consumer->addChild("Geslacht", "Vrouw");
            }
        }

        // Set relation-number attributes
        if (!empty($relation->phoneNumbers)) {
            $numbers = $customer->addChild("TelefoonNummers");
            foreach ($relation->phoneNumbers AS $phoneNumber) {
                $numbers->addChild("TelefoonNummer", $phoneNumber);
            }
        }

        // Set relation-email attribute
        $customer->addChild("EmailAdres", $relation->emailAddress);

        // Set relation-address attributes
        if (!empty($relation->addresses)) {
            $addresses = $customer->addChild("Adressen");
            foreach ($relation->addresses AS $tmpAddress) {
                $address = $addresses->addChild("Adres");
                $address->addChild("Straat", $tmpAddress->street);
                if (!empty($tmpAddress->number)) $address->addChild("Huisnummer", $tmpAddress->number);
                $address->addChild("HuisnummerToevoeging", $tmpAddress->numberSuffix);
                $address->addChild("Postcode", $tmpAddress->zipCode);
                $address->addChild("Plaats", $tmpAddress->city);
                $address->addChild("Land", $tmpAddress->country);
            }
        }

        // Set vehicle-current attributes
        $vehicle = $lead->addChild("HuidigVoertuig");
        $vehicle->addChild("Merk", $vehicleCurrent->make);
        $vehicle->addChild("Model", $vehicleCurrent->model);
        $vehicle->addChild("Uitvoering", $vehicleCurrent->type);
        $vehicle->addChild("Kenteken", $vehicleCurrent->registration);
        if (!empty($vehicleCurrent->year)) $vehicle->addChild("Bouwjaar", $vehicleCurrent->year);
        if (!empty($vehicleCurrent->fuelType)) $vehicle->addChild("SoortBrandstof", $this->convertFuelType($vehicleCurrent->fuelType));

        // Set vehicle-interest attributes
        if (!empty($vehicleInterest)) {
            $interest = $lead->addChild("GewenstVoertuig");
            $interest->addChild("Merk", $vehicleInterest->make);
            $interest->addChild("Model", $vehicleInterest->model);
            $interest->addChild("Uitvoering", $vehicleInterest->type);
            $interest->addChild("Kenteken", $vehicleInterest->registration);
            if (!empty($vehicleInterest->year)) $interest->addChild("Bouwjaar", $vehicleInterest->year);
            if (!empty($vehicleInterest->fuelType))  $interest->addChild("SoortBrandstof", $this->convertFuelType($vehicleInterest->fuelType));
            if (in_array(strtolower($vehicleInterest->vehicleCategory), ['nieuw','new'])) {
                $interest->addChild("SoortVoertuig", 'Nieuw');
            } elseif (in_array(strtolower($vehicleInterest->vehicleCategory), ['gebruikt','used'])) {
                $interest->addChild("SoortVoertuig", 'Occasion');
            }
        }

        // Set extra-info attributes
        $extraInfo = $lead->addChild("ExtraInfo");
        $group = $extraInfo->addChild("Groep");
        $group->addChild("Naam", "Autotaxatie Partners");
        $pair = $group->addChild("Pair");
        $pair->addChild("Key", "Company-id");
        $pair->addChild("Value", $leadInfo->companyId);
        $pair = $group->addChild("Pair");
        $pair->addChild("Key", "Company-name");
        $pair->addChild("Value", $leadInfo->companyName);
        $pair = $group->addChild("Pair");
        $pair->addChild("Key", "Link");
        $pair->addChild("Value", "&lt;a href=&quot;" . $leadInfo->link . "&quot;&gt;Lead bekijken&lt;/a&gt;");

        // Return
        return $xml->asXML();
    }
}