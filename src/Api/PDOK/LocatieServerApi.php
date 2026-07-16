<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://www.pdok.nl/restful-api/-/article/pdok-locatieserver-1
 */
namespace AtpCore\Api\PDOK;

use AtpCore\Api\PDOK\Response\AddressResult;
use AtpCore\Error;
use AtpCore\Extension\JsonMapperExtension;
use GuzzleHttp\Client;

class LocatieServerApi
{

    private $client;
    private $debug;
    private $logger;
    private $originalResponse;
    private $sessionId;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($hostname, $debug = false, ?\Closure $logger = null)
    {
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);
        $this->debug = $debug;
        $this->sessionId = session_id();

        // Set custom logger
        $this->logger = $logger;
    }

    /**
     * Get address information by postal-code and house-number
     *
     * @param string $postalCode
     * @param string $houseNumber
     * @return AddressResult|Error
     */
    public function getByPostalCodeHouseNumber($postalCode, $houseNumber)
    {
        try {
            // Get address-data
            $params = ["q"=>"\"$postalCode $houseNumber\" and type:adres"];
            if ($this->debug) $this->log("request", "free", json_encode($params));
            $result = $this->client->get("search/v3_1/free", ["query"=>$params]);
            if ($result->getStatusCode() != 200) {
                return new Error(data: $result, messages: ["{$result->getStatusCode()}: {$result->getReasonPhrase()}"]);
            }
            $response = json_decode($result->getBody()->getContents());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "search/v3_1/free", json_encode($response));
            return $this->mapAddressResponse($response);
        } catch (\Exception $e) {
            return new Error(data: $e, messages: [$e->getMessage()]);
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
     * @param string $endpoint
     * @param string $message
     * @return void
     */
    private function log($type, $endpoint, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$endpoint] $message";
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
     * Map response to (internal) Address-object
     *
     * @param object $response
     * @param bool $failOnUndefinedProperty
     * @return AddressResult|Error
     */
    private function mapAddressResponse($response, $failOnUndefinedProperty = true)
    {
        try {
            // Setup JsonMapper
            $responseClass = new AddressResult();
            $mapper = new JsonMapperExtension();
            $mapper->bExceptionOnUndefinedProperty = $failOnUndefinedProperty;
            $mapper->bStrictObjectTypeChecking = true;
            $mapper->bExceptionOnMissingData = true;
            $mapper->bStrictNullTypes = true;
            $mapper->bCastToExpectedType = false;

            // Map response to internal object
            $object = $mapper->map($response, $responseClass);
            $valid = $mapper->isValid($object, get_class($responseClass));
            if ($valid === false) {
                return new Error(messages: $mapper->getMessages());
            }
        } catch (\Exception $e) {
            if (stristr($e->getMessage(), "JSON property") && stristr($e->getMessage(), "does not exist in object of type AtpCore\Api\PDOK\Response\Address")) {
                return $this->mapAddressResponse($response, false);
            }
            return new Error(data: $e, messages: [$e->getMessage()]);
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