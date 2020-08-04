<?php

/**
 * API-information: https://www.dropbox.com/developers
 */
namespace AtpCore\Api\Dropbox;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private $client;
    private $clientHeaders;

    /**
     * Constructor
     *
     * @param string $apiKey
     * @param boolean $debug
     */
    public function __construct($apiKey, $debug = false)
    {
        // Set client
        $this->client = new Client(['base_uri'=>'https://api.dropboxapi.com/2/', 'http_errors'=>false, 'debug'=>$debug]);

        // Set default header for client-requests
        $this->clientHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ];

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Delete file
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile($filePath)
    {
        // Set body
        $body = ["path"=>$filePath];

        // Execute call
        $requestHeader = $this->clientHeaders;
        $result = $this->client->post('files/delete_v2', ['headers'=>$requestHeader, 'body'=>json_encode($body)]);
        $response = json_decode((string) $result->getBody());
        if (!empty($response->error_summary)) {
            $this->setErrorData($response->error);
            $this->setMessages($response->error_summary);
            return false;
        }

        // Return
        return true;
    }

}