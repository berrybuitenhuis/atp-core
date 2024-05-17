<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://bidpartnerapi.autotelexpro.nl/swagger/index.html
 */
namespace AtpCore\Api\Autotelex;

use AtpCore\Api\Autotelex\Response\Vehicle;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;
use AtpCore\Format;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private $client;
    private $debug;
    private $hostnameToken;
    private $logger;
    private $originalResponse;
    private $password;
    private $sessionId;
    private $token;
    private $username;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $hostnameToken
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($hostname, $hostnameToken, $username, $password, $debug = false, \Closure $logger = null)
    {
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);
        $this->debug = $debug;
        $this->hostnameToken = $hostnameToken;
        $this->password = $password;
        $this->username = $username;
        $this->sessionId = session_id();

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
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Get vehicle-data
            $params = ["vendorToken"=>$token, "tp"=>["ExternalID"=>$externalId]];
            if ($this->debug) $this->log("request", "GetVehicle", json_encode($params));
            $result = $this->client->GetVehicle($params);
            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($result));
            $status = $result->GetVehicleResult->Status;

            if (property_exists($status, "Code") && $status->Code == 0) {
                if (isset($result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData->BiedingId)) {
                    $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                    if ($tmp->Soort == 16) return $tmp->Waarde;
                } else {
                    $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                    if (is_array($tmp) && count($tmp) > 0) {
                        foreach ($tmp as $v) {
                            if ($v->Status == 2) continue;
                            if ($v->Soort == 16) return $v->Waarde;
                        }
                    }
                }
                return false;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Get vehicle-data
     *
     * @param int $externalId
     * @return Vehicle|object|bool
     */
    public function getVehicle($externalId, $output = null)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Get vehicle-data
            $requestHeader = ["Authorization"=> "$token->token_type $token->access_token"];
            $params = ["vehicleId"=>$externalId];
            if ($this->debug) $this->log("request", "GetVehicle", json_encode($params));
            $result = $this->client->get("GetVehicle", ["headers"=>$requestHeader, "query"=>$params]);
            if ($result->getStatusCode() != 200) {
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
            $response = json_decode($result->getBody()->getContents());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($response));
            $status = $response->status;
            if (property_exists($status, "code") && $status->code == 0) {
                if (count($response->vehicles) > 1) {
                    $this->setMessages("Found " . count($response->vehicles) . " vehicles in response, but expected 1");
                    return false;
                }
                $vehicle = current($response->vehicles);
                if ($output == "object") {
                    return $this->mapVehicleResponse($vehicle);
                } else {
                    return $vehicle;
                }
            } else {
                $this->setErrorData($status);
                $this->setMessages($status->message);
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
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
            $btw = Format::lowercase($vatMarginType) == "btw";

            // Get token
            $token = $this->getToken();
            if ($token === false) return false;

            try {
                // Compose message/parameters
                $params = [
                    "vendorToken" => $token,
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
                if (property_exists($status, "Code") && $status->Code == 0) {
                    return true;
                } else {
                    return $status;
                }
            } catch (\Exception $e) {
                $this->setMessages($e->getMessage());
                return false;
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
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Send no-interest
            $params = [
                "vendorToken" => $token,
                "vehicleId" => $externalId,
                "comment" => $comment
            ];
            if ($this->debug) $this->log("request", "NoInterest", json_encode($params));
            $result = $this->client->NoInterest($params);
            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "NoInterest", json_encode($result));
            $status = $result->NoInterestResult->Status;
            if (property_exists($status, "Code") && $status->Code == 0) {
                return true;
            } else {
                return $status;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
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
     * Get token
     *
     * @return mixed|false
     */
    private function getToken()
    {
        // Check if token already set
        if (!empty($this->token)) return $this->token;

        try {
            $tokenClient = new Client(['base_uri'=>$this->hostnameToken, 'http_errors'=>false, 'debug'=>$this->debug]);
            $requestHeader = [
                'Authorization' => 'Basic ' . base64_encode("$this->username:$this->password"),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];
            $body = ["grant_type"=>"client_credentials"];
            $result = $tokenClient->post('token', ['headers'=>$requestHeader, 'form_params'=>$body]);
            if ($result->getStatusCode() == 200) {
                $this->token = json_decode($result->getBody()->getContents());
                return $this->token;
            } else {
                $this->token = null;
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
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
     * Map response to (internal) Vehicle-object
     *
     * @param object $response
     * @param bool $failOnUndefinedProperty
     * @return Vehicle|false
     */
    private function mapVehicleResponse($response, $failOnUndefinedProperty = true)
    {
        try {
            // Setup JsonMapper
            $responseClass = new Vehicle();
            $mapper = new JsonMapperExtension();
            $mapper->bExceptionOnUndefinedProperty = $failOnUndefinedProperty;
            $mapper->bStrictObjectTypeChecking = true;
            $mapper->bExceptionOnMissingData = true;
            $mapper->bStrictNullTypes = true;
            $mapper->bCastToExpectedType = false;

            // Map response to internal object
            $object = $mapper->map($response, $responseClass);
            $valid = $mapper->isValid($object, get_class($responseClass));
            if ($valid === false) {
                $this->setMessages($mapper->getMessages());
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            if (stristr($e->getMessage(), "JSON property") && stristr($e->getMessage(), "does not exist in object of type AtpCore\Api\Autotelex\Response")) {
                return $this->mapVehicleResponse($response, false);
            }
            return false;
        }

        // Return
        return $object;
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