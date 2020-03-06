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
        $xml = new SimpleXMLElement();

        // Set leads-node
        $leads = $xml->addChild("Leads");

        // Set lead-attributes
        $lead = $leads->addChild("Lead");
        $lead->addChild("LeadType", "Sales");
        $lead->addChild("SoortLead", "Taxatie");
        $lead->addChild("Omschrijving", "Gevraagd bod tbv Inruil van " . $vehicleCurrent->make . " " . $vehicleCurrent->model . " " . $vehicleCurrent->registration . ", " . date("d-m-Y"));
        if (!empty($vehicleInterest)) {
            $lead->addChild("LeadBron", "ATP inruilTaxatie met referentie");
        } else {
            $lead->addChild("LeadBron", "ATP inruilTaxatie zonder referentie");
        }

        // Set relation-attributes
        $customer = $lead->addChild("Relatie");
        $customer->addChild("EmailAdres", "");
        if (strtolower($relation->relationType) == "particulier") {
            $consumer = $customer->addChild("Particulier");
            $consumer->addChild("Aanhef", $relation->salutation);
            $consumer->addChild("Voornaam", $relation->firstname);
            $consumer->addChild("Voorletters", $relation->initials);
            $consumer->addChild("Tussenvoegsel", $relation->infix);
            $consumer->addChild("Achternaam", $relation->lastname);
            $consumer->addChild("GeboorteDatum", $relation->dateOfBirth);
            if (in_array(strtolower($relation->gender), ['man','male'])) {
                $consumer->addChild("Geslacht", "Man");
            } elseif (in_array(strtolower($relation->gender), ['vrouw','female'])) {
                $consumer->addChild("Geslacht", "Vrouw");
            }
        } else {
            $company = $customer->addChild("Zakelijk");
            $company->addChild("Bedrijfsnaam", $relation->companyName);
            $company->addChild("KvkNummer", $relation->cocNumber);
        }

        // Set relation-number attributes
        if (!empty($relation->phoneNumbers)) {
            $numbers = $customer->addChild("TelefoonNummers");
            foreach ($relation->phoneNumbers AS $phoneNumber) {
                $numbers->addChild("TelefoonNummer", $phoneNumber);
            }
        }

        // Set relation-address attributes
        if (!empty($relation->addresses)) {
            $addresses = $customer->addChild("Adressen");
            foreach ($relation->addresses AS $address) {
                $address = $addresses->addChild("Adres");
                $address->addChild("Straat", $address->street);
                $address->addChild("Huisnummer", $address->number);
                $address->addChild("HuisnummerToevoeging", $address->numberSuffix);
                $address->addChild("Postcode", $address->zipCode);
                $address->addChild("Plaats", $address->city);
                $address->addChild("Land", $address->country);
            }
        }

        // Set vehicle-current attributes
        $vehicle = $lead->addChild("HuidigVoertuig");
        $vehicle->addChild("Merk", $vehicleCurrent->make);
        $vehicle->addChild("Model", $vehicleCurrent->model);
        $vehicle->addChild("Uitvoering", $vehicleCurrent->type);
        $vehicle->addChild("Kenteken", $vehicleCurrent->registration);
        $vehicle->addChild("Bouwjaar", $vehicleCurrent->year);
        $vehicle->addChild("SoortBrandstof", $vehicleCurrent->fuelType);

        // Set vehicle-interest attributes
        if (!empty($vehicleInterest)) {
            $interest = $lead->addChild("GewenstVoertuig");
            $interest->addChild("Merk", $vehicleInterest->make);
            $interest->addChild("Model", $vehicleInterest->model);
            $interest->addChild("Uitvoering", $vehicleInterest->type);
            $interest->addChild("Kenteken", $vehicleInterest->registration);
            $interest->addChild("Bouwjaar", $vehicleInterest->year);
            $interest->addChild("SoortBrandstof", $vehicleInterest->fuelType);
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
        $pair->addChild("Value", $leadInfo->link);

        // Return
        return $xml->asXML();
    }
}