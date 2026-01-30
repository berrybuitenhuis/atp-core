<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://vehicledataapi.autotelexpro.nl/swagger/index.html
 */
namespace AtpCore\Api\Autotelex\VehicleData;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private $client;
    private $debug;
    private $hostnameToken;
    private $logger;
    private $originalResponse;
    private $password;
    private $sessionId;
    private $token;
    private $username;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $hostnameToken
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($hostname, $hostnameToken, $username, $password, $debug = false, \Closure $logger = null)
    {
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);
        $this->debug = $debug;
        $this->hostnameToken = $hostnameToken;
        $this->password = $password;
        $this->username = $username;
        $this->sessionId = session_id();

        // Set custom logger
        $this->logger = $logger;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get data
     *
     * @param string $licensePlate
     * @return bool|object
     */
    public function getData($licensePlate)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Get vehicle-data
            $requestHeader = ["Authorization"=>"$token->token_type $token->access_token"];
            $params = ["licenseplate"=>$licensePlate];
            if ($this->debug) $this->log("request", "GetVehicledata", json_encode($params));
            $result = $this->client->get("GetVehicledata", ["headers"=>$requestHeader, "query"=>$params]);
            if ($result->getStatusCode() != 200) {
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
            $response = json_decode($result->getBody()->getContents());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "GetVehicledata", json_encode($response));
            $status = $response->status;
            if (property_exists($status, "code") && in_array($status->code, [0, 11])) {
                return $response;
            } else {
                $this->setErrorData($status);
                $this->setMessages($status->message);
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Get original-response
     *
     * @return mixed
     */
    public function getOriginalResponse()
    {
        return $this->originalResponse;
    }

    /**
     * Log message in default format
     *
     * @param string $type (request/response)
     * @param string $apiFunction
     * @param string $message
     * @return void
     */
    private function log($type, $apiFunction, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$apiFunction] $message";
        if (!empty($this->logger)) {
            $this->logger($message);
        } else {
            print("$message\n");
        }
    }

    /**
     * Get token
     *
     * @return mixed|false
     */
    private function getToken()
    {
        // Check if token already set
        if (!empty($this->token)) return $this->token;

        try {
            $tokenClient = new Client(['base_uri'=>$this->hostnameToken, 'http_errors'=>false, 'debug'=>$this->debug]);
            $requestHeader = [
                'Authorization' => 'Basic ' . base64_encode("$this->username:$this->password"),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ];
            $body = ["grant_type"=>"client_credentials"];
            $result = $tokenClient->post('token', ['headers'=>$requestHeader, 'form_params'=>$body]);
            if ($result->getStatusCode() == 200) {
                $this->token = json_decode($result->getBody()->getContents());
                return $this->token;
            } else {
                $this->token = null;
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Log message via custom log-function
     *
     * @param string $message
     * @return void
     */
    private function logger($message)
    {
        $logger = $this->logger;
        return $logger($message);
    }

    /**
     * Set original-response
     *
     * @param $originalResponse
     */
    private function setOriginalResponse($originalResponse)
    {
        $this->originalResponse = $originalResponse;
    }
}