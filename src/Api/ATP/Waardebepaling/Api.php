<?php

/**
 * API-information: API_INTEGRATION.md in atp-waardebepaling repository
 */
namespace AtpCore\Api\ATP\Waardebepaling;

use AtpCore\Api\ATP\Waardebepaling\Request\CreateValuationRequest;
use AtpCore\Api\ATP\Waardebepaling\Request\FeedbackRequest;
use AtpCore\Api\ATP\Waardebepaling\Response\CreateValuationResponse;
use AtpCore\Api\ATP\Waardebepaling\Response\Feedback;
use AtpCore\Api\ATP\Waardebepaling\Response\Valuation;
use AtpCore\Error;
use AtpCore\Extension\JsonMapperExtension;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Api
{
    private $originalResponse;
    private $sessionId;

    public function __construct(
        private string $host,
        private string $token,
        private bool $debug = false,
        private ?\Closure $logger = null)
    {
        $this->sessionId = session_id();
    }

    /**
     * Get original-response
     */
    public function getOriginalResponse(): mixed
    {
        return $this->originalResponse;
    }

    /**
     * Create valuation (processed asynchronously, result via getValuation())
     */
    public function createValuation(CreateValuationRequest $request, ?string $idempotencyKey = null): CreateValuationResponse|Error
    {
        $headers = !empty($idempotencyKey) ? ["Idempotency-Key" => $idempotencyKey] : [];
        $response = $this->execute("post", "api/valuation/v1/valuations", 202, "CreateValuation", ['body'=>json_encode($request), 'headers'=>$headers]);
        if (Error::isError($response)) return $response;

        return $this->mapResponse($response->data, new CreateValuationResponse());
    }

    /**
     * Get valuation (status, result, comparables and analyses)
     */
    public function getValuation(string $uuid): Valuation|Error
    {
        $response = $this->execute("get", "api/valuation/v1/valuations/" . rawurlencode($uuid), 200, "GetValuation");
        if (Error::isError($response)) return $response;

        return $this->mapResponse($response->data, new Valuation());
    }

    /**
     * Post feedback on valuation, identifier is uuid (preferred) or reference
     */
    public function postFeedback(string $identifier, FeedbackRequest $request): Feedback|Error
    {
        $response = $this->execute("post", "api/valuation/v1/valuations/" . rawurlencode($identifier) . "/feedback", 201, "PostFeedback", ['body'=>json_encode($request)]);
        if (Error::isError($response)) return $response;

        return $this->mapResponse($response->data, new Feedback());
    }

    /**
     * Execute request and return decoded response, or Error with status/errors/retry_after as data
     */
    private function execute(string $method, string $uri, int $expectedStatusCode, string $logName, array $options = []): object
    {
        try {
            // Execute request
            if ($this->debug && isset($options['body'])) $this->log("request", $logName, $options['body']);
            $result = $this->getClient()->request($method, $uri, $options);

            // Handle response
            $response = json_decode((string) $result->getBody());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", $logName, "{$result->getStatusCode()}: " . json_encode($response));
            if ($result->getStatusCode() != $expectedStatusCode) {
                return $this->getError($result, $response);
            }
            if (!is_object($response) || !property_exists($response, "data")) {
                return new Error(data: $result, messages: ["Empty response for ATP Waardebepaling"]);
            }
            return $response;
        } catch (\Exception $e) {
            return new Error(data: $e, messages: [$e->getMessage()]);
        }
    }

    private function getClient(): Client
    {
        $headers = [
            "Authorization" => "Bearer $this->token",
            "Content-Type" => "application/json",
            "Accept" => "application/json",
        ];
        return new Client(['base_uri'=>$this->host, 'headers'=>$headers, 'http_errors'=>false, 'debug'=>$this->debug]);
    }

    /**
     * Build error, status-code is leading (401, 404, 409, 422, 429, 5xx)
     */
    private function getError(ResponseInterface $result, mixed $response): Error
    {
        $statusCode = $result->getStatusCode();
        $message = $response->message ?? $result->getReasonPhrase();
        $data = (object) [
            "status" => $statusCode,
            "errors" => $response->errors ?? null,
            "retry_after" => $result->hasHeader("Retry-After") ? (int) $result->getHeaderLine("Retry-After") : null,
        ];

        return new Error(data: $data, messages: ["$statusCode: $message"]);
    }

    /**
     * Log message in default format
     */
    private function log(string $type, string $method, string $message): void
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
     */
    private function logger(string $message): mixed
    {
        $logger = $this->logger;
        return $logger($message);
    }

    /**
     * Map response to (internal) response-object
     */
    private function mapResponse(mixed $response, object $responseClass, bool $failOnUndefinedProperty = true): object
    {
        if (!is_object($response)) {
            return new Error(data: $response, messages: ["Invalid response for " . get_class($responseClass)]);
        }

        try {
            // Setup JsonMapper
            $mapper = new JsonMapperExtension();
            $mapper->bExceptionOnUndefinedProperty = $failOnUndefinedProperty;
            $mapper->bStrictObjectTypeChecking = true;
            $mapper->bExceptionOnMissingData = true;
            $mapper->bStrictNullTypes = true;
            $mapper->bCastToExpectedType = false;

            // Map response to internal object
            return $mapper->map($response, $responseClass);
        } catch (\Exception $e) {
            // Retry without failing on new (unknown) properties
            if ($failOnUndefinedProperty && stristr($e->getMessage(), "JSON property") && stristr($e->getMessage(), "does not exist in object of type")) {
                return $this->mapResponse($response, new $responseClass(), false);
            }
            return new Error(data: $e, messages: [$e->getMessage()]);
        }
    }

    /**
     * Set original-response
     */
    private function setOriginalResponse(mixed $originalResponse): void
    {
        $this->originalResponse = $originalResponse;
    }
}
