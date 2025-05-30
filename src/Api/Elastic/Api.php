<?php

/**
 * API-information: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html
 */

namespace AtpCore\Api\Elastic;

use AtpCore\BaseClass;
use Elastic\Elasticsearch\ClientBuilder;
use Throwable;

class Api extends BaseClass
{
    private $client;

    /**
     * Constructor
     *
     * @param string $cloudId
     * @param string $apiId
     * @param boolean $apiKey
     */
    public function __construct($cloudId, $apiId, $apiKey)
    {
        // Set client
        $this->client = ClientBuilder::create()
            ->setElasticCloudId($cloudId)
            ->setApiKey($apiKey, $apiId)
            ->build();

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Bulk insert
     *     
     * @param array $params
     * @return bool
     */
    public function bulk($params)
    {
        try {
            $res = $this->client->bulk($params);
            if ($res['errors'] == true) {
                $this->setMessages("Bulk action failed");
                $this->setErrorData($res);
                return false;
            } else {
                return true;
            }
        } catch (Throwable $e) {
            $this->setMessages("Bulk action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Count action
     *     
     * @param string $index
     * @param array $body
     * @return array|false
     */
    public function count($index, $body)
    {
        try {
            $params = [
                'index' => $index,
                'body' => $body
            ];

            $result = $this->client->count($params);
            return $result->asArray();
        } catch (Throwable $e) {
            $this->setMessages("Count action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Update index
     *     
     * @param string $index
     * @param string $id
     * @param array $body
     * @return bool
     */
    public function update($index, $id, $body)
    {
        try {
            $params = [
                'index' => $index,
                'id' => $id,
                'body'  => $body
            ];

            $res = $this->client->update($params);
            if (isset($res['result']) && in_array($res['result'], ['created', 'updated', 'noop'])) {
                return true;
            } else {
                $this->setMessages("Update action failed");
                $this->setErrorData($res);
                return false;
            }
        } catch (Throwable $e) {
            $this->setMessages("Update action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Create new index
     *     
     * @param string $index
     * @param string $id
     * @param array $body
     * @return bool
     */
    public function index($index, $id, $body)
    {
        try {
            $params = [
                'index' => $index,
                'id' => $id,
                'body'  => $body
            ];

            $res = $this->client->index($params);
            if (isset($res['result']) && in_array($res['result'], ['created', 'updated', 'noop'])) {
                return true;
            } else {
                $this->setMessages("Index action failed");
                $this->setErrorData($res);
                return false;
            }
        } catch (Throwable $e) {
            $this->setMessages("Index action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Search action
     *     
     * @param string $index
     * @param array $body
     * @return array|false
     */
    public function search($index, $body)
    {
        try {
            $params = [
                'index' => $index,
                'body' => $body
            ];

            $result = $this->client->search($params);
            return $result->asArray();
        } catch (Throwable $e) {
            $this->setMessages("Search action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Delete action
     *     
     * @param string $index
     * @param string $id
     * @return bool
     */
    public function delete($index, $id)
    {
        try {
            $params = [
                'index' => $index,
                'id'    => $id
            ];

            $res = ($this->client->delete($params));
            if (isset($res['result']) && in_array($res['result'], ['deleted', 'not_found'])) {
                return true;
            } else {
                $this->setMessages("Delete action failed");
                $this->setErrorData($res);
                return false;
            }
        } catch (Throwable $e) {
            $this->setMessages("Delete action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }
}
