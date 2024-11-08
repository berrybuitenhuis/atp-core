<?php
/**
 * API-information: https://dev.socrata.com/foundry/opendata.rdw.nl/m9d7-ebf2
 */
namespace AtpCore\Api\RDW\OpenData;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private $hostname;
    private $debug;
    private $password;
    private $sessionId;
    private $username;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param boolean $debug
     */
    public function __construct($hostname, $username = null, $password = null, $debug = false)
    {
        $this->hostname = $hostname;
        $this->sessionId = session_id();
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get (vehicle) data from RDW OpenData
     *
     * @param string $licensePlate
     * @return object|false
     */
    public function getData($licensePlate)
    {
        try {
            // Prepare request
            $client = new Client(['base_uri'=>$this->hostname, 'http_errors'=>false, 'debug'=>$this->debug]);
            $result = $client->get("resource/m9d7-ebf2.json?kenteken=" . $licensePlate);

            // Return
            if ($result->getStatusCode() === 200) {
                $data = json_decode((string) $result->getBody());
                return current($data);
            } else {
                $this->setErrorData((string) $result->getBody());
                $this->setMessages($result->getReasonPhrase());
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    public function getDataSet($code, $offset = 0, $limit = 1000)
    {
        try {
            // Prepare request
            $client = new Client(['base_uri'=>$this->hostname, 'http_errors'=>false, 'debug'=>$this->debug]);
            $result = $client->get('resource/' . $code . '.json?$offset=' . $offset . '&$limit=' . $limit);

            // Return
            if ($result->getStatusCode() === 200) {
                return json_decode((string) $result->getBody());
            } else {
                $this->setErrorData((string) $result->getBody());
                $this->setMessages($result->getReasonPhrase());
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }
}