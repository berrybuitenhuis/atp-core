<?php
/**
 * API-information: PDF-document "Autodata Trade API"
 */
namespace AtpCore\Api\Autodata;

use AtpCore\Api\Autodata\Response\Vehicle;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;
use GuzzleHttp\Client;

class TradeApi extends BaseClass
{

    private $client;
    private $clientHeaders;
    private $debug;
    private $logger;
    private $originalResponse;
    private $sessionId;
    private $token;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct(
        $hostname,
        private readonly mixed $username,
        private readonly mixed $password,
        $debug = false,
        \Closure $logger = null)
    {
        // Set client
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);
        $this->debug = $debug;
        $this->logger = $logger;
        $this->sessionId = session_id();

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Check if vehicle allowed in ASPRO-request
     *
     * @param int $externalId
     * @return bool
     */
    public function isAllowed($externalId) {
        try {
            // Get vehicle
            $res = $this->getToken($this->username, $this->password);
            if ($res === false) return false;
            $requestHeader = $this->clientHeaders;
            $result = $this->client->get('bid/' . $externalId . "/vehicle", ['headers' => $requestHeader]);
            $response = json_decode((string)$result->getBody());
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($response));
            $this->setOriginalResponse($response);

            // Return
            if (!isset($response->errors) || empty($response->errors)) {
                return true;
            } else {
                if ($response->error === "You are not authorized to use the given resource with the given credentials.") {
                    return false;
                } else {
                    return true;
                }
            }
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Get (current) bid from ASPRO-request
     *
     * @param int $externalId
     * @param $origResponse
     * @return int|object|bool
     */
    public function getBid($externalId, $origResponse = false)
    {
        // Get bid
        $res = $this->getToken($this->username, $this->password);
        if ($res === false) return false;
        $requestHeader = $this->clientHeaders;
        $result = $this->client->get('bid/' . $externalId, ['headers'=>$requestHeader]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (!isset($response->errors) || empty($response->errors)) {
            if ($origResponse === true) return $response;
            else return (int) $response->data->attributes->bidvalue;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors);
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
     * Get bid-request (by vehicle-id) from ASPRO-request
     *
     * @param int $vehicleId
     * @param boolean $origResponse
     * @param boolean $multiple
     * @return object|bool
     */
    public function getRequestByVehicleId($vehicleId, $origResponse = false, $multiple = false)
    {
        // Get bid
        $res = $this->getToken($this->username, $this->password);
        if ($res === false) return false;
        $requestHeader = $this->clientHeaders;
        $result = $this->client->get('bid?vehicle.id=' . $vehicleId, ['headers'=>$requestHeader]);
        if ($result->getStatusCode() != 200) {
            $this->setMessages("Failed call to requestByVehicleId $vehicleId: status-code {$result->getStatusCode()}");
            return false;
        }
        $response = json_decode((string) $result->getBody());
        if (empty($response)) {
            $this->setMessages("Failed call to requestByVehicleId $vehicleId: empty response-body");
            return false;
        }

        // Return
        if (!isset($response->errors) || empty($response->errors)) {
            if ($origResponse === true) return $response;
            else return ($multiple === true) ? $response->data : current($response->data);
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors);
            return false;
        }
    }

    /**
     * Get bid-requests
     *
     * @param \DateTime $startDate
     * @param boolean $origResponse
     * @return object|bool
     */
    public function getRequests($startDate = null, $origResponse = false)
    {
        // Set timestamp
        if (empty($startDate)) $timestamp = (new \DateTime('yesterday'))->getTimestamp();
        else $timestamp = $startDate->getTimestamp();

        // Get bid-requests
        $res = $this->getToken($this->username, $this->password);
        if ($res === false) return false;
        $requestHeader = $this->clientHeaders;
        $result = $this->client->get('bid?ts=' . $timestamp, ['headers'=>$requestHeader]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (!isset($response->errors) || empty($response->errors)) {
            if ($origResponse === true) return $response;
            else return $response->data;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors);
            return false;
        }
    }

    /**
     * Get vehicle from ASPRO-request
     *
     * @param int $externalId
     * @param boolean $maptoObject
     * @return Vehicle|object|false
     */
    public function getVehicle($externalId, $maptoObject = false) {
        try {
            // Get vehicle
            $res = $this->getToken($this->username, $this->password);
            if ($res === false) return false;
            $requestHeader = $this->clientHeaders;
            $result = $this->client->get('bid/' . $externalId . "/vehicle", ['headers' => $requestHeader]);
            $response = json_decode((string)$result->getBody());
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($response));
            $this->setOriginalResponse($response);

            // Return
            if (!isset($response->errors) || empty($response->errors)) {
                if ($maptoObject === false) return $response->data;
                return $this->mapVehicleResponse($response->data);
            } else {
                $this->setErrorData($response);
                $this->setMessages($response->errors);
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Send bid to ASPRO
     *
     * @param int $externalId
     * @param string $resultType
     * @param int $bid
     * @param \DateTime $expirationDate
     * @return bool|object
     */
    public function sendBid($externalId, $resultType, $bid, $expirationDate)
    {
        if ($bid > 0 || $resultType == "not_interested") {
            $res = $this->getToken($this->username, $this->password);
            if ($res === false) return false;

            // Send bid
            if ($resultType == "not_interested") {
                $body = [
                    "data"=>[
                        "type"=>"bid",
                        "attributes"=>[
                            "bidvalue"=>"",
                            "nointerest"=>true,
                            "remarks"=>""
                        ]
                    ]
                ];
            } else {
                $body = [
                    "data" => [
                        "type" => "bid",
                        "attributes" => [
                            "bidvalue" => $bid,
                            "nointerest" => false,
                            "remarks" => "Bod is geldig tot: " . $expirationDate->format('d-m-Y') . " 00:00:00"
                        ]
                    ]
                ];
            }
            $requestHeader = $this->clientHeaders;
            $result = $this->client->post('bid/' . $externalId, ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
            $response = json_decode((string) $result->getBody());

            // Return
            if (!isset($response->errors) || empty($response->errors)) {
                return true;
            } else {
                $this->setErrorData($response);
                $this->setMessages($response->errors);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get token
     *
     * @param string $clientId
     * @param string $clientSecret
     * @return boolean|string
     */
    private function getToken($clientId, $clientSecret)
    {
        // Check if token already set
        if (!empty($this->token)) return $this->token;

        $body = [
            "grant_type" => "client_credentials",
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "scope" => "app"
        ];

        $requestHeader = ["Content-Type: application/json"];
        $result = $this->client->post('oauth/access_token', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (!isset($response->errors) || empty($response->errors)) {
            $this->token = $response->token_type . " " . $response->access_token;

            // Set default header for client-requests
            $this->clientHeaders = [
                'Authorization' => $this->token,
                'Content-Type' => 'application/json',
                "User-Agent" => 'AutoData Trade v1.1',
            ];
            return $this->token;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors);
            return false;
        }
    }

    /**
     * Log message in default format
     *
     * @param string $type (request/response)
     * @param string $method
     * @param string $message
     * @return void
     */
    private function log($type, $method, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$method] $message";
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
            if (stristr($e->getMessage(), "JSON property") && stristr($e->getMessage(), "does not exist in object of type AtpCore\Api\Autodata\Response")) {
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