<?php
/**
 * API-information: PDF-document "Autodata Trade API"
 */
namespace AtpCore\Api\Autodata;

class TradeApi
{

    private $client;
    private $clientHeaders;
    private $errorData;
    private $messages;
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
        $this->client = new \GuzzleHttp\Client(array('base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug));

        // Set error-messages
        $this->messages = array();
        $this->errorData = array();

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
     * Set error-data
     *
     * @param $data
     * @return array
     */
    public function setErrorData($data)
    {
        $this->errorData = $data;
    }

    /**
     * Get error-data
     *
     * @return array
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Set error-message
     *
     * @param array $messages
     */
    public function setMessages($messages)
    {
        if (!is_array($messages)) $messages = array($messages);
        $this->messages = $messages;
    }

    /**
     * Add error-message
     *
     * @param array $message
     */
    public function addMessage($message)
    {
        if (!is_array($message)) $message = array($message);
        $this->messages = array_merge($this->messages, $message);
    }

    /**
     * Get error-messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get (current) bid from ASPRO-request
     *
     * @param int $externalId
     * @param $origResponse
     * @return int|bool
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
        if (!isset($response->errors) || empty($response->errors)) {
            // Return
            return $response->token_type . " " . $response->access_token;
        } else {
            // Return
            $this->setErrorData($response);
            $this->setMessages($response->errors);
            return false;
        }
    }

    /**
     * Send bid to ASPRO
     *
     * @param int $externalId
     * @param string $statusType
     * @param string $vatMarginType
     * @param int $bid
     * @param DateTime $expirationDate
     * @return bool|object
     */
    function sendBid($externalId, $statusType, $vatMarginType, $bid, $expirationDate)
    {
        if ($bid > 0 || $statusType == "not interested") {
            $btw = (strtolower($vatMarginType) == "btw") ? true : false;

            // Send bid
            if ($statusType == "not interested") {
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
            } elseif ($response->errors[0]->message == "bidding is no longer allowed due to status expired" ||
                $response->errors[0]->message == "bidding is no longer allowed due to status closed") {
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