<?php
/**
 * API-information: https://documentation.onesignal.com/reference
 */
namespace AtpCore\Api\OneSignal;

use AtpCore\Api\OneSignal\Entity\Notification;

class Api
{

    private $client;
    private $clientHeaders;
    private $messages;
    private $errorData;

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
        $this->client = new \GuzzleHttp\Client(array('base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug));

        // Set default header for client-requests
        $this->clientHeaders = array(
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        );

        // Set error-messages
        $this->messages = array();
        $this->errorData = array();
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
     * Send notification
     *
     * @param Notification $data
     * @return boolean|object
     */
    public function send(\AtpCore\Api\OneSignal\Entity\Notification $data)
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