<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://api.soapserver.nl/modulebeheer
 */
namespace AtpCore\Api\VoertuigSpecs;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private $client;
    private $clientHeaders;
    private $companyId;
    private $customerId;
    private $descriptionId;
    private $token;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param int $applicationId
     * @param string $apiKey
     * @param boolean $debug
     */
    public function __construct($hostname, $applicationId, $apiKey, $debug = false)
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

        // Get token
        $this->getToken($applicationId, $apiKey);
    }

    /**
     * Create new description
     *
     * @return bool
     */
    public function createDescription()
    {
        // Set payload
        $body = [
            "action" => [
                "security" => [
                    "token" => $this->token,
                ],
                "newspec" => [
                    'ipaddress' => (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "localhost",
                ],
            ],
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            $this->descriptionId = $response->response->data->spec_id;
            $this->customerId = $response->response->data->customer_id;
            return true;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }

    /**
     * Create/add image-node to description
     *
     * @param string|null $imageId
     * @return false|string
     */
    public function createImageNode($imageId = null)
    {
        // Set payload
        $vimImageId = (!empty($imageId)) ? $imageId : $this->descriptionId . "-extra-" . explode(" ", microtime())[0] . mt_rand();
        $body = [
            "action" => [
                "security" => [
                    "token" => $this->token,
                    "spec_id" => $this->descriptionId,
                ],
                "newimage" => [
                    "vim_image_id" => $vimImageId
                ],
            ],
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            return $vimImageId;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }

    /**
     * Set description as finished
     *
     * @return bool
     */
    public function finishDescription()
    {
        // Set payload
        $body = [
            "action" => [
                "security" => [
                    "token" => $this->token,
                    "spec_id" => $this->descriptionId,
                ],
                "finishspec" => [],
            ],
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            return true;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }

    /**
     * Get description-data
     *
     * @return false|object
     */
    public function getDescription()
    {
        // Set payload
        $body = [
            "get" => [
                "security" => [
                    "token" => $this->token,
                    "spec_id" => $this->descriptionId
                ],
                "all" => []
            ]
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            return $response->response->data;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }

    /**
     * Save description as valuation-request
     *
     * @param int $valuationTypeId
     * @param array $valuationParams
     * @return false|int
     */
    public function saveValuation($valuationTypeId, $valuationParams)
    {
        $params = [];
        $params["valuationType"] = $valuationTypeId;
        $params["valuationParams"] = $valuationParams;
        if (!empty($this->companyId)) $params["valuationParams"]["companyId"] = $this->companyId;
        if (!empty($this->personId)) $params["valuationParams"]["personId"] = $this->personId;

        // Validate required-fields for saving valuation
        $error = null;
        $valid = $this->validateInputRequirements(2);
        if ($valid === true) {
            // Set payload
            $body = [
                "action" => [
                    "security" => [
                        "token" => $this->token,
                        "spec_id" => $this->descriptionId
                    ],
                    "savevaluation" => $params
                ]
            ];

            // Execute call
            $requestHeader = $this->clientHeaders;
            $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
            $response = json_decode((string) $result->getBody());

            // Return
            if (isset($response->response)) {
                return $response->response->data->valuationId;
            } else {
                $this->setErrorData($response);
                $this->setMessages($response->errors->error->message);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Set description-id (for continue existing description)
     *
     * @param int $descriptionId
     */
    public function setDescription($descriptionId)
    {
        $this->descriptionId = $descriptionId;
    }

    /**
     * Update description-data
     *
     * @param array $data
     * @return bool
     */
    public function updateDescription($data)
    {
        // Set payload
        $body = [
            "update" => [
                "security" => [
                    "token" => $this->token,
                    "spec_id" => $this->descriptionId
                ],
                "data" => $data
            ]
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            return true;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }

    /**
     * Upload image (by URL) to description
     *
     * @param string $imageName
     * @param string $imageUrl
     * @param string|null $imageId
     * @return bool
     */
    public function uploadImageByUrl($imageName, $imageUrl, $imageId = null)
    {
        // Create image-node if not available
        if (empty($imageId)) $imageId = $this->createImageNode();

        // Prepare URI
        $uri = sprintf("upload?spec_id=%s&token=%s&vim_image_id=%s&fileName=%s&sourceUrl=%s", $this->descriptionId, $this->token, $imageId, $imageName, $imageUrl);

        // Execute call
        $requestHeader = $this->clientHeaders;
        $requestHeader['Content-Type'] = "text/plain";
        $result = $this->client->post($uri, ['headers'=>$requestHeader, 'body'=>$imageUrl]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            return true;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }

    /**
     * Get token
     *
     * @param int $applicationId
     * @param string $apiKey
     * @return bool
     */
    private function getToken($applicationId, $apiKey)
    {
        // Set payload
        $body = [
            "action" => [
                "security" => [
                    "token" => "",
                ],
                "newtoken" => [
                    "application_id" => $applicationId,
                    "requestToken" => $apiKey,
                ],
            ],
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            $this->token = $response->response->data->token;
            if (!empty($response->response->data->companyId)) {
                $this->companyId = $response->response->data->companyId;
            }
            return true;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }

    /**
     * Validate provided input by required-fields
     *
     * @param int $pluginId
     * @return bool
     */
    private function validateInputRequirements($pluginId)
    {
        // Set payload
        $body = [
            "action" => [
                "security" => [
                    "token" => $this->token,
                    "spec_id" => $this->descriptionId,
                ],
                "validateInputRequirements" => [
                    "id" => $pluginId,
                ],
            ],
        ];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->put('', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (isset($response->response)) {
            return true;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors->error->message);
            return false;
        }
    }
}