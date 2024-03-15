<?php
/**
 * API-information: https://documentation.onesignal.com/reference
 */
namespace AtpCore\Api\OneSignal;

use AtpCore\BaseClass;
use AtpCore\Api\OneSignal\Entity\Notification;
use AtpCore\Format;
use Exception;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private $client;
    private $clientHeaders;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $apiKey
     * @param boolean $debug
     */
    public function __construct($hostname, $apiKey, $debug = false)
    {
        // Set client
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);

        // Set default header for client-requests
        $this->clientHeaders = [
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get subscriptions of user
     *
     * @param Notification $data
     * @return array|null|false
     * @throws Exception
     */
    public function getSubscriptions($appId, $aliasId, $aliasType = "external_id")
    {
        $requestHeader = $this->clientHeaders;
        $result = $this->client->get("apps/$appId/users/by/$aliasType/$aliasId", ['headers'=>$requestHeader]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (!isset($response->errors) || empty($response->errors)) {
            return $response->subscriptions;
        } elseif (isset($response->errors[0]) && is_object($response->errors[0]) && property_exists($response->errors[0], "title") && strpos(Format::lowercase($response->errors[0]->title), "doesn't match an existing user")) {
            return null;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors);
            return false;
        }
    }

    /**
     * Send notification
     *
     * @param Notification $data
     * @return boolean|object
     * @throws Exception
     */
    public function send(Notification $data)
    {
        // Convert input-data into body
        $body = $data->encode(true);

        $requestHeader = $this->clientHeaders;
        $result = $this->client->post('notifications', ['headers'=>$requestHeader, 'body'=>$body]);
        $response = json_decode((string) $result->getBody());

        // Return
        if (!isset($response->errors) || empty($response->errors)) {
            return $response;
        } else {
            $this->setErrorData($response);
            $this->setMessages($response->errors);
            return false;
        }
    }
}