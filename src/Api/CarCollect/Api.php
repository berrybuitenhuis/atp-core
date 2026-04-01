<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://docs.carcollect.com/
 */
namespace AtpCore\Api\CarCollect;

use AtpCore\Api\CarCollect\Response\Vehicle;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;
use AtpCore\Format;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Api extends BaseClass
{

    private $branch;
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
     * @param HttpClientInterface $httpClient
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($host, $username, $password, private readonly HttpClientInterface $httpClient, $debug = false, ?\Closure $logger = null)
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
     * @param boolean $maptoObject
     * @return Vehicle|object|false
     */
    public function getVehicle($externalId, $maptoObject = true)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Set query
            $queryFields = $this->getQueryFields();
            if ($queryFields === false) return false;
            $selectionSet = $this->buildSelectionSet($queryFields);
            $query = "{ getTradeDossier(id: $externalId) $selectionSet }";

            // Get vehicle-data
            if ($this->debug) $this->log("request", "GetVehicle", $query);
            $result = $this->executeQuery($query, [], $token);
            if ($result === false) return false;

            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($result));

            $data = $result->getTradeDossier;
            if ($maptoObject === false) return $data;
            else return $this->mapVehicleResponse($data);
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
     * @param string|null $comment
     * @return \stdClass|false
     */
    public function sendBid($tradeDossierId, $amount, $comment = null)
    {
        // Get branch and token
        $token = $this->getToken();
        if ($token === false) return false;
        if (empty($this->branch)) {
            $this->setMessages("No bid-branch found");
            return false;
        }

        try {
            $amount = (int) $amount;
            // Override bid, not possible to have 0 for bid, otherwise receive error-message "Unprocessable Entity")
            if ($amount === 0) $amount = 1;
            $comment = preg_replace('/\s+/', ' ', Format::trim($comment) ?? "");
            $escapedComment = addslashes($comment);
            $escapedBranch = addslashes($this->branch);

            $query = <<<GRAPHQL
                mutation createBidApi(\$tradeDossierId: ID!) {
                    createBidApi(tradeDossierId: \$tradeDossierId, bid: {amount: $amount, branch: "$escapedBranch", comment: "$escapedComment"}) {
                        id
                        amount
                        comment
                    }
                }
            GRAPHQL;

            $result = $this->executeQuery($query, ['tradeDossierId' => $tradeDossierId], $token);
            if ($result === false) return false;

            return $result->createBidApi;
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Execute a GraphQL query/mutation via Symfony HttpClient
     *
     * @param string $query
     * @param array $variables
     * @param string|null $token
     * @return object|false
     */
    private function executeQuery(string $query, array $variables = [], ?string $token = null)
    {
        $headers = ['Content-Type' => 'application/json'];
        if (!empty($token)) {
            $headers['Authorization'] = "Bearer $token";
        }

        $body = ['query' => $query];
        if (!empty($variables)) {
            $body['variables'] = $variables;
        }

        try {
            $response = $this->httpClient->request('POST', $this->host, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $data = json_decode($response->getContent(false));

            if (isset($data->errors) && !empty($data->errors)) {
                $errorMessages = array_map(fn($e) => $e->message, $data->errors);
                $this->setMessages(implode('; ', $errorMessages));
                return false;
            }

            return $data->data;
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Build a GraphQL selection set string from a fields array
     *
     * @param array $fields
     * @return string
     */
    private function buildSelectionSet(array $fields): string
    {
        $parts = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                $parts[] = $field;
            } elseif (is_array($field)) {
                $parts[] = $field['name'] . ' ' . $this->buildSelectionSet($field['fields']);
            }
        }
        return '{ ' . implode(' ', $parts) . ' }';
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
                $fields[] = ['name' => $property->getName(), 'fields' => $subfields];
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
     * Get token
     *
     * @return string|false
     */
    private function getToken()
    {
        // Check if token already set
        if (!empty($this->token)) return $this->token;

        try {
            $query = sprintf(
                'mutation { loginApiAccount(email: "%s", password: "%s") { id access_token default_branch } }',
                addslashes($this->username),
                addslashes($this->password)
            );

            $result = $this->executeQuery($query);
            if ($result === false) return false;

            // Set branch and token
            $this->branch = $result->loginApiAccount->default_branch;
            $this->token = $result->loginApiAccount->access_token;

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