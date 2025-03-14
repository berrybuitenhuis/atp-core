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
    public function __construct(
        $hostname,
        private readonly mixed $username,
        private readonly mixed $password,
        $debug = false)
    {
        // Set client
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);

        // Reset error-messages
        $this->resetErrors();

        // Set default header for client-requests
        $this->clientHeaders = [
            'Accept' => 'application/json',
            'Content-Type' => 'text/json',
        ];
    }

    /**
     * Get ATB54: Translation Autotelex-accessories to KIR-items
     *
     * @param array $accessoryIds
     * @param string $vehicleType
     * @return object|bool
     */
    public function getATB54($accessoryIds, $vehicleType)
    {
        // Check token
        $this->getToken($this->username, $this->password);
        if (empty($this->token)) {
            $this->setMessages("Not authorized");
            return false;
        }

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
                        "autotelex_ids" => json_encode($accessoryIds),
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
     * Get ATB56: Retrieve VWE vehicle-data by license-plate
     *
     * @param string $licensePlate
     * @param int|null $mileage
     * @param boolean $historicOwners
     * @param boolean $historicStatus
     * @return object|bool
     */
    public function getATB56($licensePlate, $mileage = null, $historicOwners = false, $historicStatus = false)
    {
        // Check token
        $this->getToken($this->username, $this->password);
        if (empty($this->token)) {
            $this->setMessages("Not authorized");
            return false;
        }

        // Set payload
        $body = [
            "get" => [
                "security" => [
                    "userid" => $this->userId,
                    "hash" => $this->token,
                ],
                "data" => [
                    "message" => "56",
                    "parameters" => [
                        "registration" => $licensePlate,
                        "mileage" => $mileage,
                        "historic_owners" => $historicOwners,
                        "historic_status" => $historicStatus,
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
     * Get ATB57: Retrieve VWE type-data by license-plate
     *
     * @param string $licensePlate
     * @param int $atlCode
     * @param string $bodyType
     * @param string $category
     * @param boolean $valuable
     * @param boolean $distinctive
     * @return object|bool
     */
    public function getATB57($licensePlate, $atlCode, $bodyType, $category = null, $valuable = false, $distinctive = false)
    {
        // Check token
        $this->getToken($this->username, $this->password);
        if (empty($this->token)) {
            $this->setMessages("Not authorized");
            return false;
        }

        // Set payload
        $body = [
            "get" => [
                "security" => [
                    "userid" => $this->userId,
                    "hash" => $this->token,
                ],
                "data" => [
                    "message" => "57",
                    "parameters" => [
                        "registration" => $licensePlate,
                        "atl_code" => $atlCode,
                        'category' => $category,
                        "valuating" => $valuable,
                        "typical" => $distinctive,
                        "typical_standard" => $distinctive,
                        "overrides" => [
                            "detail_carrosserie" => $bodyType
                        ],
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
        // Check if token already set
        if (!empty($this->token)) return true;

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
        $requestHeader = $this->clientHeaders;
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