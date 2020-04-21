<?php
/**
 * API-information: https://documentation.onesignal.com/reference
 */
namespace AtpCore\Api\OneSignal;

use AtpCore\BaseClass;
use AtpCore\Api\OneSignal\Entity\Notification;
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
     * Send notification
     *
     * @param Notification $data
     * @return boolean|object
     * @throws Exception
     */
    public function send(Notification $data)
    {
        // Convert input-data into body
        $body = $data->encode();

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