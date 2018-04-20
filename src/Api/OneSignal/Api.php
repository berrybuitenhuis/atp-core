<?php
/**
 * API-information: https://documentation.onesignal.com/reference
 */
namespace AtpCore\Api\OneSignal;

class Api
{

    private $appId;
    private $client;
    private $clientHeaders;
    private $messages;
    private $errorData;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $apiKey
     * @param string $appId
     * @param boolean $debug
     */
    public function __construct($hostname, $apiKey, $appId, $debug = false)
    {
        // Set application
        $this->appId = $appId;

        // Set client
        $this->client = new \Guzzle\Http\Client($hostname, array('http_errors'=>false, 'debug'=>$debug));

        // Set default header for client-requests
        $this->clientHeaders = array(
            'Authorization' => 'Basic ' . $apiKey,
            'Content-Type' => 'application/json',
        );
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

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
     * Create (send) notification
     *
     * @param int $stocknumber
     * @param string $site
     * @return boolean|array
     */
    public function createNotification(\AtpCore\Api\OneSignal\Entity\Notification $data)
    {
        // Convert input-data into body
        $body = $data->encode();

        $requestHeader = $this->clientHeaders;
        $request = $this->client->post('/notifications', $requestHeader, $body);
        $result = $request->send();

        $response = json_decode((string) $result->getBody());
        if (!isset($response->errors) || empty($response->errors)) {
            // Return
            return $response;
        } else {
            // Return
            $this->setErrorData($response);
            $this->setMessages($response->errors);
            return false;
        }
    }

}