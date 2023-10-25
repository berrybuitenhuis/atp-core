<?php /** @noinspection PhpUndefinedMethodInspection */

namespace AtpCore\Api\GoRemarketing;

use AtpCore\Api\GoRemarketing\Response\XML\Vehicle;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;

class Xml extends BaseClass
{

    private $debug;
    private $host;
    private $logger;
    private $originalResponse;
    private $password;
    private $port;
    private $sessionId;
    private $username;

    /**
     * Constructor
     *
     * @param string $host
     * @param string $port
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($host, $port, $username, $password, $debug = false, \Closure $logger = null)
    {
        $this->debug = $debug;
        $this->host = $host;
        $this->password = $password;
        $this->port = $port;
        $this->sessionId = session_id();
        $this->username = $username;

        // Set custom logger
        $this->logger = $logger;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get vehicle-data
     *
     * @param string $directory
     * @param string $fileName
     * @return Vehicle|false
     */
    public function getVehicle($directory, $fileName)
    {
        try {
            // Setup SFTP-connection
            $sftp = new \AtpCore\File\SFTP($this->host, $this->port, $this->username, $this->password);

            // Get vehicle-data
            if ($this->debug) $this->log("request", "GetVehicle", "$directory/$fileName");
            $xmlData = $sftp->getFileContent($directory, $fileName);
            if ($xmlData === false) {
                $this->setMessages($sftp->getMessages());
                return false;
            } elseif (\AtpCore\Input::isXml($xmlData) === false) {
                $this->setMessages("No (valid) XML-file found");
                return false;
            }
            if ($this->debug) $this->log("response", "GetVehicle", $xmlData);
            $this->setOriginalResponse($xmlData);
            $response = \AtpCore\Input::convertXML(simplexml_load_string($xmlData));
            if ($this->debug) $this->log("converted response", "GetVehicle", json_encode($response));
            return $this->mapVehicleResponse($response);
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
     * @param string $method
     * @param string $message
     * @return void
     */
    private function log($type, $method, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$method] $message";
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
     * Map response to (internal) Vehicle-object
     *
     * @param object $response
     * @return Vehicle|false
     */
    private function mapVehicleResponse($response)
    {
        try {
            // Setup JsonMapper
            $responseClass = new Vehicle();
            $mapper = new JsonMapperExtension();
            $mapper->bExceptionOnUndefinedProperty = true;
            $mapper->bStrictObjectTypeChecking = true;
            $mapper->bExceptionOnMissingData = true;
            $mapper->bStrictNullTypes = true;
            $mapper->bCastToExpectedType = false;

            // Map response to internal object
            $object = $mapper->map($response, $responseClass);
            $valid = $mapper->isValid($object, get_class($responseClass));
            if ($valid === false) {
                $this->setMessages($mapper->getMessages());
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }

        // Return
        return $object;
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