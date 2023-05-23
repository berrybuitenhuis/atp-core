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
            $responseData = simplexml_load_string($response->standaardDataRequestResult);
            $result = \AtpCore\Input::convertJson($responseData->resultaat);

            // Check for valid status-code
            if ($result->code == "00") {
                $data = new \stdClass();
                if (isset($responseData->rubrieken->rdwInfoCheck)) $data->rdwInfoCheck = $this->convertData($responseData->rubrieken->rdwInfoCheck->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwEigenaarCheck)) $data->rdwEigenaarCheck = $this->convertData($responseData->rubrieken->rdwEigenaarCheck->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwInfoBasic)) $data->rdwInfoBasic = $this->convertData($responseData->rubrieken->rdwInfoBasic->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwInfoAdvanced)) $data->rdwInfoAdvanced = $this->convertData($responseData->rubrieken->rdwInfoAdvanced->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->milieuInfoBasic)) $data->milieuInfoBasic = $this->convertData($responseData->rubrieken->milieuInfoBasic->children('http://www.xmlmode.nl/interdata/milieu'));
                if (isset($responseData->rubrieken->rdwHistInfoAdvanced)) $data->rdwHistInfoAdvanced = $this->convertData($responseData->rubrieken->rdwHistInfoAdvanced->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwStatusHistorie)) $data->rdwStatusHistorie = $this->convertData($responseData->rubrieken->rdwStatusHistorie->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->rdwInfoEtg)) $data->rdwInfoEtg = $this->convertData($responseData->rubrieken->rdwInfoEtg->children('http://www.xmlmode.nl/interdata/rdw'));
                if (isset($responseData->rubrieken->atlTechInfoAdvanced)) $data->atlTechInfoAdvanced = $this->convertData($responseData->rubrieken->atlTechInfoAdvanced->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlPriceInfoBasic)) $data->atlPriceInfoBasic = $this->convertData($responseData->rubrieken->atlPriceInfoBasic->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlTransmissie)) $data->atlTransmissie = $this->convertData($responseData->rubrieken->atlTransmissie->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlFoto)) $data->atlFoto = $this->convertData($responseData->rubrieken->atlFoto->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlOptieStandaard)) $data->atlOptieStandaard = $this->convertData($responseData->rubrieken->atlOptieStandaard->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlOptieFabriek)) $data->atlOptieFabriek = $this->convertData($responseData->rubrieken->atlOptieFabriek->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlOptiePakket)) $data->atlOptiePakket = $this->convertData($responseData->rubrieken->atlOptiePakket->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->atlMmtInfo)) $data->atlMmtInfo = $this->convertData($responseData->rubrieken->atlMmtInfo->children('http://www.xmlmode.nl/interdata/atl'));
                if (isset($responseData->rubrieken->rdwOkrCheck)) $data->rdwOkrCheck = $this->convertData($responseData->rubrieken->rdwOkrCheck->children('http://www.xmlmode.nl/interdata/rdw'));

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
     * Convert received data into corresponding types
     *
     * @param object $data
     * @return object
     */
    private function convertData($data)
    {
        $data = \AtpCore\Input::convertJson($data);

        // Iterate data
        foreach (get_object_vars($data) AS $key => $value) {
            // Replace empty object into null
            if ($value instanceOf \stdClass && empty((array) $value)) $data->$key = null;
            // Replace string false into boolean
            elseif (is_string($value) && strtolower($value) === "false") $data->$key = false;
            // Replace string true into boolean
            elseif (is_string($value) && strtolower($value) === "true") $data->$key = true;
        }

        // Return
        return $data;
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