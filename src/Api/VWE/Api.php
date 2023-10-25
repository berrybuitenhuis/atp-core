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
            if (!empty($atlCode)) $xml->writeElement("uitvoeringId", $atlCode);
            if (!empty($mileage)) $xml->writeElement("tellerstand", $mileage);
            if (!empty($mileageType)) $xml->writeElement("eenheid", $mileageType);
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
            $responseData = simplexml_load_string($response->standaardDataRequestResult);
            $result = $responseData->resultaat;

            // Check for valid status-code
            if ($result->code == "00") {
                $data = new \stdClass();
                if (isset($responseData->rubrieken->rdwInfoCheck)) $data->rdwInfoCheck = \AtpCore\Input::convertXML($responseData->rubrieken->rdwInfoCheck->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwEigenaarCheck)) $data->rdwEigenaarCheck = \AtpCore\Input::convertXML($responseData->rubrieken->rdwEigenaarCheck->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwInfoBasic)) $data->rdwInfoBasic = \AtpCore\Input::convertXML($responseData->rubrieken->rdwInfoBasic->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwInfoAdvanced)) $data->rdwInfoAdvanced = \AtpCore\Input::convertXML($responseData->rubrieken->rdwInfoAdvanced->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->milieuInfoBasic)) $data->milieuInfoBasic = \AtpCore\Input::convertXML($responseData->rubrieken->milieuInfoBasic->children('http://www.xmlmode.nl/interdata/milieu'));
                if (isset($responseData->rubrieken->rdwHistInfoAdvanced)) $data->rdwHistInfoAdvanced = \AtpCore\Input::convertXML($responseData->rubrieken->rdwHistInfoAdvanced->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwStatusHistorie)) $data->rdwStatusHistorie = \AtpCore\Input::convertXML($responseData->rubrieken->rdwStatusHistorie->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwInfoEtg)) $data->rdwInfoEtg = \AtpCore\Input::convertXML($responseData->rubrieken->rdwInfoEtg->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->atlTechInfoAdvanced)) $data->atlTechInfoAdvanced = \AtpCore\Input::convertXML($responseData->rubrieken->atlTechInfoAdvanced->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlPriceInfoBasic)) $data->atlPriceInfoBasic = \AtpCore\Input::convertXML($responseData->rubrieken->atlPriceInfoBasic->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlTransmissie)) $data->atlTransmissie = \AtpCore\Input::convertXML($responseData->rubrieken->atlTransmissie->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlFoto)) $data->atlFoto = \AtpCore\Input::convertXML($responseData->rubrieken->atlFoto->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlOptieStandaard)) $data->atlOptieStandaard = \AtpCore\Input::convertXML($responseData->rubrieken->atlOptieStandaard->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlOptieFabriek)) $data->atlOptieFabriek = \AtpCore\Input::convertXML($responseData->rubrieken->atlOptieFabriek->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlOptiePakket)) $data->atlOptiePakket = \AtpCore\Input::convertXML($responseData->rubrieken->atlOptiePakket->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlMmtInfo)) $data->atlMmtInfo = \AtpCore\Input::convertXML($responseData->rubrieken->atlMmtInfo->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->rdwOkrCheck)) $data->rdwOkrCheck = \AtpCore\Input::convertXML($responseData->rubrieken->rdwOkrCheck->children('http://www.xmlmode.nl/interdata/rdw'));

                return $data;
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
     * Get original-response
     *
     * @return object
     */
    public function getOriginalResponse()
    {
        return $this->originalResponse;
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