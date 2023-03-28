<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://docs.carcollect.com/
 */
namespace AtpCore\Api\CarCollect;

use AtpCore\Api\CarCollect\Response\Vehicle;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;
use GraphQL\Client;
use GraphQL\Mutation;
use GraphQL\Query;
use GraphQL\RawObject;

class Api extends BaseClass
{

    private $branches;
    private $debug;
    private $host;
    private $logger;
    private $originalResponse;
    private $password;
    private $sessionId;
    private $token;
    private $username;

    /**
     * Constructor
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($host, $username, $password, $debug = false, \Closure $logger = null)
    {
        $this->host = $host;
        $this->debug = $debug;
        $this->password = $password;
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
     * @param int $externalId
     * @return Vehicle|object|bool
     */
    public function getVehicle($externalId)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Set query
            $queryFields = $this->getQueryFields();
            if ($queryFields === false) return false;
            $query = (new Query('getTradeDossier'))
                ->setArguments(['id'=>$externalId])
                ->setSelectionSet($queryFields);

            // Get vehicle-data
            if ($this->debug) $this->log("request", "GetVehicle", json_encode($query));
            $response = $this->getClient($token)->runQuery($query);
            $this->setOriginalResponse($response->getData());
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($response->getData()));
            return $this->mapVehicleResponse($response->getData()->getTradeDossier);
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
     * Send bid to CarCollect
     *
     * @param string $tradeDossierId
     * @param int $amount
     * @return array|false
     */
    public function sendBid($tradeDossierId, $amount)
    {
        // Get branch and token
        $token = $this->getToken();
        if ($token === false) return false;
        if (count($this->branches) == 0 || count($this->branches) > 1) {
            if (count($this->branches) == 0) $this->setMessages("No bid-branch found");
            else $this->setMessages("Multiple bid-branches found (count: " . count($this->branches) . ") ");
            return false;
        }
        $branch = current($this->branches)->id;

        try {
            $amount = (int) $amount;
            $mutation = (new Mutation('createBidApi'))
                ->setArguments(['tradeDossierId' => $tradeDossierId, 'bid' => new RawObject("{amount: $amount, branch: \"$branch\"}")])
                ->setSelectionSet(['id', 'amount']);

            $response = $this->getClient()->runQuery($mutation);
            return $response->getData();
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Get query-fields based on object
     *
     * @param string $objectName
     * @return array|false
     */
    private function getQueryFields($objectName = __NAMESPACE__ . "\Response\Vehicle")
    {
        // Get class-properties
        $reflection = new \ReflectionClass($objectName);
        $properties = $reflection->getProperties();

        // Iterate class-properties
        foreach ($properties AS $property) {
            // Check if property is primitive or class
            if ($this->isPrimitive($property)) {
                $fields[] = $property->getName();
            } else {
                // Extract class out of doc-comment
                $docComment = $property->getDocComment();
                $className = str_ireplace("@var", "", $docComment);
                $className = str_ireplace("|null", "", $className);
                $className = str_ireplace("[]", "", $className);
                $className = preg_replace("/[^A-Za-z0-9]/", "", $className);
                $className = __NAMESPACE__ . "\Response\\$className";
                if (!class_exists($className)) {
                    $this->setMessages("Unknown class $className for docComment $docComment");
                    return false;
                }

                // Get fields of class
                $subfields = $this->getQueryFields($className);
                if ($subfields === false) return false;
                $fields[] = (new Query($property->getName()))->setSelectionSet($subfields);
            }
        }

        // Return
        return $fields;
    }

    /**
     * Check if property is a primitive type (like string, integer boolean)
     *
     * @param \ReflectionProperty $property
     * @return bool
     */
    private function isPrimitive($property)
    {
        $primitiveTypes = [\string::class, \int::class, \integer::class, bool::class, \boolean::class];
        $docComment = $property->getDocComment();
        foreach ($primitiveTypes as $primitiveType) {
            if (strpos($docComment, "@var $primitiveType") !== false) {
                return true;
            }
        }

        // Return
        return false;
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
     * Initialize GraphQl-client
     *
     * @param string|null $token
     * @return Client
     */
    private function getClient($token = null)
    {
        if (!empty($token)) {
            $client = new Client($this->host, ["Authorization" => "Bearer $token"]);
        } else {
            $client = new Client($this->host);
        }

        // Return
        return $client;
    }

    /**
     * Get token
     *
     * @return string|false
     */
    private function getToken()
    {
        // Check if token already set
        if (!empty($this->token)) return $this->token;

        try {
            // Get token
            $mutation = (new Mutation('loginApi'))
                ->setArguments(['email'=>$this->username, 'password'=>$this->password])
                ->setSelectionSet([
                    'id', 'email', 'access_token',
                    (new Query('branches'))->setSelectionSet(['id', 'name'])
                ]);

            $response = $this->getClient()->runQuery($mutation);
            $result = $response->getData();

            // Set branches and token
            $this->branches = $result->loginApi->branches;
            $this->token = $result->loginApi->access_token;

            // Return
            return $this->token;
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