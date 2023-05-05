<?php
/**
 * API-information: https://interdata.vwe.nl/
 */
namespace AtpCore\Api\VWE;

use AtpCore\BaseClass;
use Laminas\Soap\Client;

class Api extends BaseClass
{

    private $client;
    private $debug;
    private $logger;
    private $originalResponse;
    private $password;
    private $sessionId;
    private $username;

    /**
     * Constructor
     *
     * @param string $wsdl
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($wsdl, $username, $password, $debug = false, \Closure $logger = null)
    {
        $this->client = new Client($wsdl, ['encoding' => 'UTF-8']);
        $this->client->setSoapVersion(SOAP_1_2);
        $this->sessionId = session_id();
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;

        // Set custom logger
        $this->logger = $logger;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get (vehicle) data from VWE Interdata
     *
     * @param string $licensePlate
     * @param string $messageType
     * @param string|null $atlCode
     * @param integer|null $mileage
     * @param string|null $mileageType
     * @return object|false
     */
    public function getData($licensePlate, $messageType, $atlCode = null, $mileage = null, $mileageType = null)
    {
        try {
            // Prepare request
            $xml = new \XMLWriter();
            $xml->openMemory();
            $xml->startElement("bericht");
            $xml->startElement("authenticatie");
            $xml->writeElement("naam", $this->username);
            $xml->writeElement("wachtwoord", $this->password);
            $xml->writeElement("berichtsoort", $messageType);
            $xml->writeElement("referentie", "");
            $xml->endElement();
            $xml->startElement("parameters");
            $xml->writeElement("kenteken", $licensePlate);
            $xml->endElement();
            $xml->endElement();
            $request = $xml->outputMemory(true);
            $params = [
                "requestXml" => $request,
            ];

            // Get vehicle-data
            if ($this->debug) $this->log("request", "StandaardDataRequest", json_encode($params));
            $response = $this->client->StandaardDataRequest($params);
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "StandaardDataRequest", json_encode($response));
            $result = \AtpCore\Input::convertJson(simplexml_load_string($response->standaardDataRequestResult));

            // Check for valid status-code
            if ($result->resultaat->code == "00") {
                return $result->rubrieken;
            } else {
                $this->setMessages("{$result->resultaat->code}: {$result->resultaat->omschrijving}");
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Log message in default format
     *
     * @param string $type (request/response)
     * @param string $soapFunction
     * @param string $message
     * @return void
     */
    private function log($type, $soapFunction, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$soapFunction] $message";
        if (!empty($this->logger)) {
            $this->logger($message);
        } else {
            print("$message\n");
        }
    }

    /**
     * Set original-response
     *
     * @param $originalResponse
     */
    private function setOriginalResponse($originalResponse)
    {
        $this->originalResponse = $originalResponse;
    }
}