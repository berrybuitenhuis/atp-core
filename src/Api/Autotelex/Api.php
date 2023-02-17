<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: http://api.autotelexpro.nl/autotelexproapi.svc?singleWsdl
 */
namespace AtpCore\Api\Autotelex;

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
    private $token;
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
        $this->client->setSoapVersion(SOAP_1_1);
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
     * Get (current) bid from Autotelex-request
     *
     * @param $externalId
     * @return int|bool
     */
    public function getBid($externalId)
    {
        // Set token
        $this->setToken();

        // Get vehicle-data
        $params = ["vendorToken"=>$this->token, "tp"=>["ExternalID"=>$externalId]];
        if ($this->debug) $this->log("request", "GetVehicle", json_encode($params));
        $result = $this->client->GetVehicle($params);
        $this->setOriginalResponse($result);
        if ($this->debug) $this->log("response", "GetVehicle", json_encode($result));
        $status = $result->GetVehicleResult->Status;

        if ($status->Code == 0) {
            if (isset($result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData->BiedingId)) {
                $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                if ($tmp->Soort == 16) return $tmp->Waarde;
            } else {
                $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                if (is_array($tmp) && count($tmp) > 0) {
                    foreach ($tmp AS $v) {
                        if ($v->Status == 2) continue;
                        if ($v->Soort == 16) return $v->Waarde;
                    }
                }
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Get (vehicle) data from Autotelex-PRO
     *
     * @param string $registration
     * @param string|null $atlCode
     * @param string|null $mileage
     * @return object|false
     */
    public function getData($registration, $atlCode = null, $mileage = null)
    {
        // Set token
        $this->setToken();

        // Set parameters
        $vehicleParams = ["kenteken" => $registration];
        if (!empty($atlCode)) $vehicleParams["AutotelexUitvoeringID"] = $atlCode;
        if (!empty($mileage)) $vehicleParams["kilometerstand"] = $mileage;
        $params = ["token"=>$this->token, "vehicle"=>$vehicleParams];

        if ($this->debug) $this->log("request", "GetVehicleDataPRO", json_encode($params));
        $result = $this->client->GetVehicleDataPRO($params);
        $this->setOriginalResponse($result);
        if ($this->debug) $this->log("response", "GetVehicleDataPRO", json_encode($result));
        $status = $result->GetVehicleDataPROResult->Status;

        // Check for valid status-code
        // Status-code: 0, OK
        // Status-code: 1, OK, with addition info (choose type-commercial)
        // Status-code: 2, General error
        // Status-code: 3, Customer-token invalid
        // Status-code: 4, Vendor-token invalid
        if (in_array($status->GenericCode, [0, 1]) || ($status->GenericCode == 2 && stristr($status->Message, "Ongeldig of onbekend kenteken"))) {
            return $result->GetVehicleDataPROResult;
        } else {
            return false;
        }
    }

    /**
     * Get vehicle-data
     *
     * @param int $externalId
     * @return bool|object
     */
    public function getVehicle($externalId)
    {
        // Set token
        $this->setToken();

        // Get vehicle-data
        $params = ["vendorToken"=>$this->token, "tp"=>["ExternalID"=>$externalId]];
        if ($this->debug) $this->log("request", "GetVehicle", json_encode($params));
        $result = $this->client->GetVehicle($params);
        $this->setOriginalResponse($result);
        if ($this->debug) $this->log("response", "GetVehicle", json_encode($result));
        $status = $result->GetVehicleResult->Status;
        if ($status->Code == 0) {
            return $result->GetVehicleResult;
        } else {
            $this->setErrorData($status);
            $this->setMessages($status->Message);
            return false;
        }
    }

    /**
     * Send bid to Autotelex
     *
     * @param int $externalId
     * @param string $resultType
     * @param string $vatMarginType
     * @param int $bid
     * @param \DateTime $expirationDate
     * @param string $comment
     * @param int $rdwIdentificationNumber
     * @return bool|object
     */
    public function sendBid($externalId, $resultType, $vatMarginType, $bid, $expirationDate, $comment = null, $rdwIdentificationNumber = null)
    {
        if ($resultType == "not_interested") {
            return $this->sendNoInterest($externalId, $comment);
        }

        if ($bid > 0) {
            $btw = (strtolower($vatMarginType) == "btw") ? true : false;

            // Set token
            $this->setToken();

            // Compose message/parameters
            $params = [
                "vendorToken" => $this->token,
                "ibp" => [
                    "ExternalID" => $externalId,
                    "SoortBod" => 16,
                    "Bod" => $bid,
                    "isBTWVoertuig" => $btw,
                    "Status" => 3,
                    "InclExclBTW" => "Incl. BTW",
                    "GeldigTot" => $expirationDate->format('c'),
                    "Naam" => "Autotaxatie (Autotaxatie)",
                    "Opmerking" => $comment
                ],
            ];
            if (!empty($rdwIdentificationNumber)) {
                $params["ibp"]["Buyer"] = [
                    "RdwNumber" => $rdwIdentificationNumber
                ];
            }

            // Send bid
            if ($this->debug) $this->log("request", "InsertBod", json_encode($params));
            $result = $this->client->InsertBod($params);
            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "InsertBod", json_encode($result));
            $status = $result->InsertBodResult;
            if ($status->Code == 0) {
                return true;
            } else {
                return $status;
            }
        } else {
            return false;
        }
    }

    /**
     * Send no-interest to Autotelex
     *
     * @param int $externalId
     * @param string $comment
     * @return bool|object
     */
    public function sendNoInterest($externalId, $comment = null)
    {
        // Set token
        $this->setToken();

        // Send no-interest
        $params = [
            "vendorToken" => $this->token,
            "vehicleId" => $externalId,
            "comment" => $comment
        ];
        if ($this->debug) $this->log("request", "NoInterest", json_encode($params));
        $result = $this->client->NoInterest($params);
        $this->setOriginalResponse($result);
        if ($this->debug) $this->log("response", "NoInterest", json_encode($result));
        $status = $result->NoInterestResult;
        if ($status->Code == 0) {
            return true;
        } else {
            return $status;
        }
    }

    /**
     * Get original-response
     *
     * @return mixed
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
     * Log message via custom log-function
     *
     * @param string $message
     * @return void
     */
    private function logger($message)
    {
        $logger = $this->logger;
        return $logger($message);
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

    /**
     * Set token
     * 
     * @return void
     */
    private function setToken()
    {
        // Check if token already set
        if (!empty($this->token)) return;

        $params = ["username"=>$this->username, "password"=>$this->password];
        $result = $this->client->GetVendorToken($params);
        $status = $result->GetVendorTokenResult->Status;
        if ($status->Code == 0) {
            $this->token = $result->GetVendorTokenResult->Token;
        } else {
            $this->token = null;
        }
    }
}