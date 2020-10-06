<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://www.autotaxatiebank.nl
 */
namespace AtpCore\Api\ATB;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private $client;
    private $clientHeaders;
    private $token;
    private $userId;

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
        $this->getToken($username, $password);

        // Set default header for client-requests
        $this->clientHeaders = [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Get ATB54: Translation Autotelex-accessories to KIR-items
     *
     * @param array $accessoryIds
     * @param string $vehicleType
     * @return object|bool
     */
    function getATB54($accessoryIds, $vehicleType)
    {
        // Set payload
        $body = [
            "get" => [
                "security" => [
                    "userid" => $this->userId,
                    "hash" => $this->token,
                ],
                "data" => [
                    "message" => "54",
                    "parameters" => [
                        "autotelex_ids" => $accessoryIds,
                        "vehicletype" => $vehicleType,
                    ],
                ],
            ],
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('getdata/', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->data)) {
            return $response->data;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response);
            return false;
        }
    }

    /**
     * Get token
     *
     * @return boolean
     */
    private function getToken($username, $password)
    {
        // Set payload
        $body = [
            "get" => [
                "security" => [
                    "username" => $username,
                    "password" => md5($password),
                ],
            ],
        ];

        // Execute call
        $requestHeader = ["Content-Type: application/json"];
        $result = $this->client->put('gethash/', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->hash) && !empty($response->hash)) {
            $this->token = $response->hash;
            $this->userId = $response->id;
            return true;
        } else {
            $this->setErrorData($response);
            $this->setMessages("Unauthorized for ATB");
            return false;
        }
    }
}