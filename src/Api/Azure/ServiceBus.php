<?php
/**
 * API-information: https://docs.microsoft.com/en-us/rest/api/azure/
 */
namespace AtpCore\Api\Azure;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class ServiceBus extends BaseClass
{

    private $client;
    private $clientHeaders;
    private $version;

    /**
     * Constructor
     *
     * @param string $connectionString
     */
    public function __construct($connectionString, $version = "2015-01", $debug = false)
    {
        // Get config out of connection-string
        $config = $this->getConfig($connectionString);

        // Set client
        $hostname = $config["Endpoint"] . $config["EntityPath"] . "/";
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);
        $this->version = $version;

        // Set default header for client-requests
        $this->clientHeaders = [
            'Authorization' => $this->generateSASToken($hostname, $config["SharedAccessKeyName"], $config["SharedAccessKey"]),
        ];

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Retrieve messages in peek-mode from queue
     *
     * @return object|void|false
     */
    public function getMessagesPeekMode()
    {
        try {
            // Get messages
            $result = $this->client->get("messages/head?api-version={$this->version}", ["headers"=>$this->clientHeaders]);
            switch ($result->getStatusCode()) {
                case "200":
                case "201":
                    return [
                        "headers" => $result->getHeaders(),
                        "messages" => (string) $result->getBody(),
                    ];
                case "204":
                    return;
                default:
                    $this->setErrorData((string) $result->getBody());
                    $this->setMessages("Error retrieving service-bus messages ({$result->getStatusCode()}: {$result->getReasonPhrase()})");;
                    return false;
            }
        } catch (\Throwable $e) {
            $this->setErrorData($e->getTrace());
            $this->setMessages("Failed retrieving service-bus messages ({$e->getCode()}: {$e->getMessage()})");
            return false;
        }
    }

    public function deleteMessage($messageId, $lockToken)
    {
        try {
            // Delete message
            $result = $this->client->delete("messages/$messageId/$lockToken", ["headers"=>$this->clientHeaders]);
            switch ($result->getStatusCode()) {
                case "200":
                    return true;
                default:
                    $this->setErrorData((string) $result->getBody());
                    $this->setMessages("Error deleting message (id: $messageId) from service-bus ({$result->getStatusCode()}: {$result->getReasonPhrase()})");
                    return false;
            }
        } catch (ServiceException $e) {
            $this->setErrorData($e->getTrace());
            $this->setMessages("Failed deleting message (id: $messageId) from service-bus ({$e->getCode()}: {$e->getMessage()})");
            return false;
        }
    }

    /**
     * Generate SAS-token (documentation: https://docs.microsoft.com/nl-nl/rest/api/eventhub/generate-sas-token)
     *
     * @param string $hostname
     * @param string $sharedAccessKeyName
     * @param string $sharedAccessKey
     * @return string
     */
    private function generateSASToken($hostname, $sharedAccessKeyName, $sharedAccessKey)
    {
        // Set variables for SAS-token
        $targetUri = strtolower(rawurlencode(strtolower($hostname)));
        $expires = time() + 60;

        // Generate signature
        $toSign = $targetUri . "\n" . $expires;
        $signature = rawurlencode(base64_encode(hash_hmac('sha256', $toSign, $sharedAccessKey, true)));

        // Return
        return "SharedAccessSignature sr=$targetUri&sig=$signature&se=$expires&skn=$sharedAccessKeyName";
    }

    /**
     * Get config out of connection-string
     *
     * @param string $connectionString
     * @return array
     */
    private function getConfig($connectionString)
    {
        // Initialize config
        $config = [];

        // Split config-elements out of connection-string
        $configElements = explode(";", $connectionString);

        // Iterate config-elements
        foreach ($configElements AS $configElement) {
            // Split name and value out of config-element
            list($name, $value) = explode("=", $configElement, 2);

            // Replace protocol for endpoint
            if ($name == "Endpoint") $value = str_ireplace("sb://", "https://", $value);

            // Set element to config
            $config[$name] = $value;
        }

        // Return
        return $config;
    }
}