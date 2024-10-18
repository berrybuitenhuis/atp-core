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
     * Extend existing bid
     *
     * @param $externalId
     * @param \DateTime $expirationDate
     * @return bool|object
     */
    public function extendBid($externalId, $expirationDate)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Compose message/parameters
            $params = [
                "vehicleId" => $externalId,
                "newExpiryDate" => $expirationDate->format('c'),
            ];

            // Extend bid
            $requestHeader = ["Authorization"=> "$token->token_type $token->access_token"];
            if ($this->debug) $this->log("request", "ExtendBid", json_encode($params));
            $result = $this->client->post("ExtendBid", ["headers"=>$requestHeader, "body"=>json_encode($params)]);
            if ($result->getStatusCode() != 200) {
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
            $response = json_decode($result->getBody()->getContents());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "ExtendBid", json_encode($response));
            if (property_exists($response, "code") && $response->code == 0) {
                return true;
            } elseif (property_exists($response, "status") && $response->status->code == 0) {
                return true;
            } else {
                $response = (property_exists($response, "status")) ? $response->status : $response;
                $responseCode = $response->code ?? null;
                $responseMessage = $response->message ?? null;
                $this->setMessages("$responseCode: $responseMessage");
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
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
            $vehicleData = $this->getVehicle($externalId, "object");
            if ($vehicleData === false) return false;

            // Get bids from vehicle-data
            $bids = $vehicleData->voertuigVariabelen->biedingen;
            if (is_array($bids) && count($bids) > 0) {
                foreach ($bids as $bid) {
                    if ($bid->status == 2) continue; // status "aanvraag"
                    if ($bid->soort == 16) return $bid->waarde; // type "Autotaxatie Partners"
                }
            }

            // Return
            return false;
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
     * @return bool
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
                    "externalID" => $externalId,
                    "soortBod" => 16,
                    "bod" => $bid,
                    "isBTWVoertuig" => $btw,
                    "status" => 3,
                    "inclExclBTW" => "Incl. BTW",
                    "geldigTot" => $expirationDate->format('c'),
                    "naam" => "Autotaxatie (Autotaxatie)",
                    "opmerking" => $comment
                ];
                if (!empty($rdwIdentificationNumber)) {
                    $params["buyer"] = [
                        "rdwNumber" => $rdwIdentificationNumber
                    ];
                }

                // Send bid
                $requestHeader = ["Authorization"=> "$token->token_type $token->access_token"];
                if ($this->debug) $this->log("request", "InsertBid", json_encode($params));
                $result = $this->client->post("InsertBid", ["headers"=>$requestHeader, "body"=>json_encode($params)]);
                if ($result->getStatusCode() != 200) {
                    $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                    return false;
                }
                $response = json_decode($result->getBody()->getContents());
                $this->setOriginalResponse($response);
                if ($this->debug) $this->log("response", "InsertBid", json_encode($response));
                if (property_exists($response, "code") && $response->code == 0) {
                    return true;
                } elseif (property_exists($response, "status") && $response->status->code == 0) {
                    return true;
                } else {
                    $response = (property_exists($response, "status")) ? $response->status : $response;
                    $responseCode = $response->code ?? null;
                    $responseMessage = $response->message ?? null;
                    $this->setMessages("$responseCode: $responseMessage");
                    return false;
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
     * @return bool
     */
    public function sendNoInterest($externalId, $comment = null)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Compose message/parameters
            $params = [
                "vehicleId" => $externalId,
                "comment" => $comment
            ];

            // Send no-interest
            $requestHeader = ["Authorization"=> "$token->token_type $token->access_token"];
            if ($this->debug) $this->log("request", "NoInterest", json_encode($params));
            $result = $this->client->post("NoInterest", ["headers"=>$requestHeader, "query"=>$params]);
            if ($result->getStatusCode() != 200) {
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
            $response = json_decode($result->getBody()->getContents());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "NoInterest", json_encode($response));
            if (property_exists($response, "code") && $response->code == 0) {
                return true;
            } elseif (property_exists($response, "status") && $response->status->code == 0) {
                return true;
            } else {
                $response = (property_exists($response, "status")) ? $response->status : $response;
                $responseCode = $response->code ?? null;
                $responseMessage = $response->message ?? null;
                $this->setMessages("$responseCode: $responseMessage");
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