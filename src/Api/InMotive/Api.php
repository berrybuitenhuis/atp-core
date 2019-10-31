<?php

/**
 * API-information: http://ws-acc.dataexe.nl/DataexeWS/PromatieAT2/WS.asmx?wsdl
 */
namespace AtpCore\Api\InMotive;

use AtpCore\BaseClass;
use Zend\Soap\Client;

class Api extends BaseClass
{

    private $client;
    private $data;
    private $debug;
    private $password;
    private $transporter;
    private $username;

    /**
     * Constructor
     *
     * @param string $wsdl
     * @param string $username
     * @param string $password
     * @param string $transporter
     * @param boolean $debug
     */
    public function __construct($wsdl, $username, $password, $transporter, $debug = false)
    {
        // Set authentication
        $this->username = $username;
        $this->password = $password;
        $this->transporter = $transporter;

        // Set client
        $this->client = new Client($wsdl, ['encoding' => 'UTF-8']);
        $this->client->setSoapVersion(SOAP_1_1);
        $this->debug = $debug;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Create transport-request
     *
     * @return bool
     */
    public function createTransport()
    {
        // Execute SOAP-call
        $response = $this->client->__call("LoadCRUD", ["parameters"=>$this->data]);
        if ($response->LoadCRUDResult->success === false) {
            $this->setMessages($response->LoadCRUDResult->reasonFailure);
            return false;
        }

        // Return
        return true;
    }

    public function setAddress($type, $companyName, $personName, $street, $number, $zipcode, $city, $country, $email, $phone, $comment)
    {
        // Add data to array
        $this->data[$type]["name"] = html_entity_decode($companyName);
        if (trim($personName) != "") $this->data[$type]["contact"] = html_entity_decode($personName);
        $this->data[$type]["street"] = html_entity_decode($street);
        $this->data[$type]["housenumber"] = trim($number);
        $this->data[$type]["zipcode"] = $zipcode;
        $this->data[$type]["city"] = html_entity_decode($city);
        if (trim($country) != "") $this->data[$type]["country"] = html_entity_decode($country);
        if (trim($phone) != "") $this->data[$type]["telephone"] = $phone;
        if (trim($email) != "") $this->data[$type]["email"] = $email;
        if (trim($comment) != "") $this->data[$type]["remarks"] = html_entity_decode($comment);
    }

    public function setGeneral($transportId, $orderNumber, $debtor, $dateDeadline)
    {
        // Initialize XML
        $this->data = [];

        // Add data to array
        $this->data["from"] = $this->username;
        $this->data["password"] = $this->password;
        $this->data["to"] = $this->transporter;
        $this->data["externalID"] = $transportId;
        $this->data["crud"] = 'Create';
        $this->data["ordernumber"] = $orderNumber;
        $this->data["debtor"] = $debtor;
        $this->data["transportPrice"] = "0.00";
        $this->data["orderdate"] = (new \DateTime())->format('Y-m-d\TH:i:s');
        $this->data["transportdate"]["dateType"] = (!empty($config['deadline_type'])) ? $config['deadline_type'] : "Before";
        $this->data["transportdate"]["date1"] = $dateDeadline->format('Y-m-d\TH:i:s');
    }

    public function setVehicle($vin, $registration, $vehicleType, $make, $model, $color, $comment)
    {
        // Add data to array
        if (!empty($vin)) {
            $this->data["load"]["vin"] = $vin;
        } else {
            $this->data["load"]["licenseplate"] = $registration;
        }
        $this->data["load"]["make"] = html_entity_decode(trim(preg_replace('/ {2,}/', ' ', $make)));
        $this->data["load"]["model"] = html_entity_decode(trim(preg_replace('/ {2,}/', ' ', $model)));
        $this->data["load"]["color"] = html_entity_decode($color);
        $this->data["load"]["class"] = $vehicleType;
        $this->data["load"]["weight"] = "0";
        if (trim($comment) != "") $this->data["remarks"] = html_entity_decode($comment);
    }

}