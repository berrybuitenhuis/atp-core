<?php

/**
 * API-information: https://api.nl.jp.cars/doc
 */
namespace AtpCore\Api\JpCars;

use AtpCore\Api\JpCars\Request\AuctionImportRequest;
use AtpCore\Api\JpCars\Request\ValuateRequest;
use AtpCore\Api\JpCars\Response\AuctionImportResponse;
use AtpCore\Api\JpCars\Response\ValuateResponse;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;
use GuzzleHttp\Client;

class Api extends BaseClass
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

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get original-response
     */
    public function getOriginalResponse(): mixed
    {
        return $this->originalResponse;
    }

    /**
     * Add single vehicle to auction-purchase
     */
    public function auctionImport(AuctionImportRequest $request, string $auctionName): AuctionImportResponse|false
    {
        try {
            // Initialize request
            $client = $this->getClient();
            $body = json_encode($request);
            if ($this->debug) $this->log("request", "AuctionImport", $body);
            // Execute request
            $result = $client->post('api-purchase/auction/import', ['query'=>['auction_name'=>$auctionName], 'body'=>$body]);
            // Handle response
            $response = json_decode((string) $result->getBody());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "AuctionImport", json_encode($response));
            if (!empty($response->error)) {
                $this->setMessages("$response->error: $response->error_message");
                return false;
            }
            return $this->mapResponse($response, new AuctionImportResponse());
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Valuate single vehicle
     */
    public function valuate(ValuateRequest $request): ValuateResponse|false
    {
        try {
            // Initialize request
            $client = $this->getClient();
            $body = json_encode($request);
            if ($this->debug) $this->log("request", "Valuate", $body);
            // Execute request
            $result = $client->post('api/valuate', ['body'=>$body]);
            // Handle response
            $response = json_decode((string) $result->getBody());
            $this->setOriginalResponse($response);
            if ($this->debug) $this->log("response", "Valuate", json_encode($response));
            if (!empty($response->error)) {
                $this->setMessages("$response->error: $response->error_message");
                return false;
            }
            return $this->mapResponse($response, new ValuateResponse());
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    private function getClient(): Client
    {
        $headers = [
            "Authorization" => "Bearer $this->token",
            "Content-Type" => "application/json",
        ];
        return new Client(['base_uri'=>$this->host, 'headers'=>$headers, 'http_errors'=>false, 'debug'=>$this->debug]);
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
    private function mapResponse(object $response, mixed $responseClass): ValuateResponse|false
    {
        try {
            // Setup JsonMapper
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
     */
    private function setOriginalResponse(mixed $originalResponse)
    {
        $this->originalResponse = $originalResponse;
    }
}
