<?php

/**
 * API-information: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html
 */

namespace AtpCore\Api\Elastic;

use AtpCore\BaseClass;
use Exception;
use Elasticsearch\ClientBuilder;
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
            ->setApiKey($apiId, $apiKey)
            ->build();

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Bulk insert
     *     
     * @param string $index
     * @param string $id
     * @param array $body
     * @return bool
     */

    /*
    $params['index']                  = (string) Default index for items which don't provide one
    $params['type']                   = DEPRECATED (string) Default document type for items which don't provide one
    $params['wait_for_active_shards'] = (string) Sets the number of shard copies that must be active before proceeding with the bulk operation. Defaults to 1, meaning the primary shard only. Set to `all` for all shard copies, otherwise set to any non-negative value less than or equal to the total number of copies for the shard (number of replicas + 1)
    $params['refresh']                = (enum) If `true` then refresh the effected shards to make this operation visible to search, if `wait_for` then wait for a refresh to make this operation visible to search, if `false` (the default) then do nothing with refreshes. (Options = true,false,wait_for)
    $params['routing']                = (string) Specific routing value
    $params['timeout']                = (time) Explicit operation timeout
    $params['_source']                = (list) True or false to return the _source field or not, or default list of fields to return, can be overridden on each sub-request
    $params['_source_excludes']       = (list) Default list of fields to exclude from the returned _source field, can be overridden on each sub-request
    $params['_source_includes']       = (list) Default list of fields to extract and return from the _source field, can be overridden on each sub-request
    $params['pipeline']               = (string) The pipeline id to preprocess incoming documents with
    $params['body']                   = (array) The operation definition and data (action-data pairs), separated by newlines (Required)
    */
    public function bulk($index)
    {
        try {
            $params = [
                'index' => $index,
            ];

            $this->client->bulk($params);
        } catch (Throwable $e) {
            $this->setMessages("Bulk action failed");
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
    /*
    $params['id']                     = (string) Document ID (Required)
    $params['index']                  = (string) The name of the index (Required)
    $params['type']                   = DEPRECATED (string) The type of the document
    $params['wait_for_active_shards'] = (string) Sets the number of shard copies that must be active before proceeding with the update operation. Defaults to 1, meaning the primary shard only. Set to `all` for all shard copies, otherwise set to any non-negative value less than or equal to the total number of copies for the shard (number of replicas + 1)
    $params['_source']                = (list) True or false to return the _source field or not, or a list of fields to return
    $params['_source_excludes']       = (list) A list of fields to exclude from the returned _source field
    $params['_source_includes']       = (list) A list of fields to extract and return from the _source field
    $params['lang']                   = (string) The script language (default: painless)
    $params['refresh']                = (enum) If `true` then refresh the effected shards to make this operation visible to search, if `wait_for` then wait for a refresh to make this operation visible to search, if `false` (the default) then do nothing with refreshes. (Options = true,false,wait_for)
    $params['retry_on_conflict']      = (number) Specify how many times should the operation be retried when a conflict occurs (default: 0)
    $params['routing']                = (string) Specific routing value
    $params['timeout']                = (time) Explicit operation timeout
    $params['if_seq_no']              = (number) only perform the update operation if the last operation that has changed the document has the specified sequence number
    $params['if_primary_term']        = (number) only perform the update operation if the last operation that has changed the document has the specified primary term
    $params['body']                   = (array) The request definition requires either `script` or partial `doc` (Required)
    */
    public function update($index, $id, $body)
    {
        try {
            $params = [
                'index' => $index,
                'id' => $id,
                'body'  => $body
            ];

            return ($this->client->update($params));
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
    /*
    $params['id']                     = (string) Document ID
    $params['index']                  = (string) The name of the index (Required)
    $params['type']                   = DEPRECATED (string) The type of the document
    $params['wait_for_active_shards'] = (string) Sets the number of shard copies that must be active before proceeding with the index operation. Defaults to 1, meaning the primary shard only. Set to `all` for all shard copies, otherwise set to any non-negative value less than or equal to the total number of copies for the shard (number of replicas + 1)
    $params['op_type']                = (enum) Explicit operation type (Options = index,create) (Default = index)
    $params['refresh']                = (enum) If `true` then refresh the affected shards to make this operation visible to search, if `wait_for` then wait for a refresh to make this operation visible to search, if `false` (the default) then do nothing with refreshes. (Options = true,false,wait_for)
    $params['routing']                = (string) Specific routing value
    $params['timeout']                = (time) Explicit operation timeout
    $params['version']                = (number) Explicit version number for concurrency control
    $params['version_type']           = (enum) Specific version type (Options = internal,external,external_gte,force)
    $params['if_seq_no']              = (number) only perform the index operation if the last operation that has changed the document has the specified sequence number
    $params['if_primary_term']        = (number) only perform the index operation if the last operation that has changed the document has the specified primary term
    $params['pipeline']               = (string) The pipeline id to preprocess incoming documents with
    $params['body']                   = (array) The document (Required)
    */
    public function index($index, $id, $body)
    {
        try {
            $params = [
                'index' => $index,
                'id' => $id,
                'body'  => $body
            ];

            return ($this->client->index($params));
        } catch (Throwable $e) {
            $this->setMessages("Index action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Search action
     *     
     * @param array $params
     * @return bool
     */
    /*
    $params['index']                         = (list) A comma-separated list of index names to search; use `_all` or empty string to perform the operation on all indices
    $params['type']                          = DEPRECATED (list) A comma-separated list of document types to search; leave empty to perform the operation on all types
    $params['analyzer']                      = (string) The analyzer to use for the query string
    $params['analyze_wildcard']              = (boolean) Specify whether wildcard and prefix queries should be analyzed (default: false)
    $params['ccs_minimize_roundtrips']       = (boolean) Indicates whether network round-trips should be minimized as part of cross-cluster search requests execution (Default = true)
    $params['default_operator']              = (enum) The default operator for query string query (AND or OR) (Options = AND,OR) (Default = OR)
    $params['df']                            = (string) The field to use as default where no field prefix is given in the query string
    $params['explain']                       = (boolean) Specify whether to return detailed information about score computation as part of a hit
    $params['stored_fields']                 = (list) A comma-separated list of stored fields to return as part of a hit
    $params['docvalue_fields']               = (list) A comma-separated list of fields to return as the docvalue representation of a field for each hit
    $params['from']                          = (number) Starting offset (default: 0)
    $params['ignore_unavailable']            = (boolean) Whether specified concrete indices should be ignored when unavailable (missing or closed)
    $params['ignore_throttled']              = (boolean) Whether specified concrete, expanded or aliased indices should be ignored when throttled
    $params['allow_no_indices']              = (boolean) Whether to ignore if a wildcard indices expression resolves into no concrete indices. (This includes `_all` string or when no indices have been specified)
    $params['expand_wildcards']              = (enum) Whether to expand wildcard expression to concrete indices that are open, closed or both. (Options = open,closed,none,all) (Default = open)
    $params['lenient']                       = (boolean) Specify whether format-based query failures (such as providing text to a numeric field) should be ignored
    $params['preference']                    = (string) Specify the node or shard the operation should be performed on (default: random)
    $params['q']                             = (string) Query in the Lucene query string syntax
    $params['routing']                       = (list) A comma-separated list of specific routing values
    $params['scroll']                        = (time) Specify how long a consistent view of the index should be maintained for scrolled search
    $params['search_type']                   = (enum) Search operation type (Options = query_then_fetch,dfs_query_then_fetch)
    $params['size']                          = (number) Number of hits to return (default: 10)
    $params['sort']                          = (list) A comma-separated list of <field>:<direction> pairs
    $params['_source']                       = (list) True or false to return the _source field or not, or a list of fields to return
    $params['_source_excludes']              = (list) A list of fields to exclude from the returned _source field
    $params['_source_includes']              = (list) A list of fields to extract and return from the _source field
    $params['terminate_after']               = (number) The maximum number of documents to collect for each shard, upon reaching which the query execution will terminate early.
    */
    public function search($params)
    {
        try {
            return $this->client->search($params);
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
    /*
    $params['id']                     = (string) The document ID (Required)
    $params['index']                  = (string) The name of the index (Required)
    $params['type']                   = DEPRECATED (string) The type of the document
    $params['wait_for_active_shards'] = (string) Sets the number of shard copies that must be active before proceeding with the delete operation. Defaults to 1, meaning the primary shard only. Set to `all` for all shard copies, otherwise set to any non-negative value less than or equal to the total number of copies for the shard (number of replicas + 1)
    $params['refresh']                = (enum) If `true` then refresh the effected shards to make this operation visible to search, if `wait_for` then wait for a refresh to make this operation visible to search, if `false` (the default) then do nothing with refreshes. (Options = true,false,wait_for)
    $params['routing']                = (string) Specific routing value
    $params['timeout']                = (time) Explicit operation timeout
    $params['if_seq_no']              = (number) only perform the delete operation if the last operation that has changed the document has the specified sequence number
    $params['if_primary_term']        = (number) only perform the delete operation if the last operation that has changed the document has the specified primary term
    $params['version']                = (number) Explicit version number for concurrency control
    $params['version_type']           = (enum) Specific version type (Options = internal,external,external_gte,force)
    */
    public function delete($index, $id)
    {
        try {
            $params = [
                'index' => $index,
                'id'    => $id
            ];

            return ($this->client->delete($params));
        } catch (Throwable $e) {
            $this->setMessages("Delete action failed");
            $this->setErrorData($e->getMessage());
            return false;
        }
    }
}
