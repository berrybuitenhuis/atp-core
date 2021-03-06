<?php
/**
 * API-information: PDF-document "Autodata Trade API"
 */
namespace AtpCore\Api\Autodata;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class TradeApi extends BaseClass
{

    private $client;
    private $clientHeaders;
    private $token;

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

        // Get token
        $this->token = $this->getToken($username, $password);

        // Set default header for client-requests
        $this->clientHeaders = [
            'Authorization' => $this->token,
            'Content-Type' => 'application/json',
            "User-Agent" => 'AutoData Trade v1.1',
        ];
    }

    /**
     * Get (current) bid from ASPRO-request
     *
     * @param int $externalId
     * @param $origResponse
     * @return int|object|bool
     */
    function getBid($externalId, $origResponse = false)
    {
        // Get bid
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
     * Get bid-request (by vehicle-id) from ASPRO-request
     *
     * @param int $vehicleId
     * @param boolean $origResponse
     * @param boolean $multiple
     * @return object|bool
     */
    function getRequestByVehicleId($vehicleId, $origResponse = false, $multiple = false)
    {
        // Get bid
        $requestHeader = $this->clientHeaders;
        $result = $this->client->get('bid?vehicle.id=' . $vehicleId, ['headers'=>$requestHeader]);
        if ($result->getStatusCode() != 200) {
            $this->setMessages("Failed call to requestByVehicleId: {$result->getStatusCode()}");
            return false;
        }
        $response = json_decode((string) $result->getBody());

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
    function getRequests($startDate = null, $origResponse = false)
    {
        // Set timestamp
        if (empty($startDate)) $timestamp = (new \DateTime('yesterday'))->getTimestamp();
        else $timestamp = $startDate->getTimestamp();

        // Get bid-requests
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
     * Get token
     *
     * @param string $clientId
     * @param string $clientSecret
     * @return boolean|string
     */
    private function getToken($clientId, $clientSecret)
    {
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
            return $response->token_type . " " . $response->access_token;
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
     * @param boolean $origResponse
     * @return object|bool
     */
    function getVehicle($externalId, $origResponse = false) {
        // Get vehicle
        $requestHeader = $this->clientHeaders;
        $result = $this->client->get('bid/' . $externalId . "/vehicle", ['headers'=>$requestHeader]);
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
     * Send bid to ASPRO
     *
     * @param int $externalId
     * @param string $resultType
     * @param int $bid
     * @param \DateTime $expirationDate
     * @return bool|object
     */
    function sendBid($externalId, $resultType, $bid, $expirationDate)
    {
        if ($bid > 0 || $resultType == "not_interested") {
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
            } elseif (stristr($response->errors[0]->message, "bidding is no longer allowed due to status")) {
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
}