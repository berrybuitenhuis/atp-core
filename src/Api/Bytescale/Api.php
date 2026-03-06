<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://www.bytescale.com/docs
 */
namespace AtpCore\Api\Bytescale;

use AtpCore\BaseClass;
use GuzzleHttp\Client;

class Api extends BaseClass
{

    private string $accountId;
    private Client $client;
    private bool $debug;
    private \Closure|null $logger;
    private $originalResponse;
    private string $sessionId;
    private string $token;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $accountId
     * @param string $token
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($hostname, $accountId, $token, $debug = false, \Closure $logger = null)
    {
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);
        $this->accountId = $accountId;
        $this->debug = $debug;
        $this->sessionId = session_id();
        $this->token = $token;

        // Set custom logger
        $this->logger = $logger;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Delete file
     *
     * @param string $path
     * @param string $filename
     * @return bool
     */
    public function deleteFile($path, $filename)
    {
        try {
            $params = [
                "filePath" => "/$path/$filename",
            ];
            $requestHeader = ["Authorization"=>"Bearer $this->token"];
            if ($this->debug) $this->log("request", "FileDelete", json_encode($params));
            $result = $this->client->delete("$this->accountId/files", ["headers"=>$requestHeader, "query"=>$params]);
            if ($result->getStatusCode() != 200) {
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
            $response = json_decode($result->getBody()->getContents());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "FileDelete", json_encode($response));
            return true;
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Get file-details of file
     *
     * @param string $path
     * @param string $filename
     * @return bool|object
     */
    public function getFileDetails($path, $filename)
    {
        try {
            $params = [
                "filePath" => "/$path/$filename",
            ];
            $requestHeader = ["Authorization"=>"Bearer $this->token", "Content-Type"=>"application/json"];
            if ($this->debug) $this->log("request", "FileDetails", json_encode($params));
            $result = $this->client->get("$this->accountId/files/details", ["headers"=>$requestHeader, "query"=>$params]);
            if ($result->getStatusCode() != 200) {
                $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
                return false;
            }
            $response = json_decode($result->getBody()->getContents());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "FileDetails", json_encode($response));
            return $response;
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
     * @param string $soapFunction
     * @param string $message
     * @return void
     */
    private function log($type, $soapFunction, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$soapFunction] $message";
        if (!empty($this->logger)) {
            $this->logger($message);
        } else {
            print("$message\n");
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