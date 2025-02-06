<?php
/**
 * API-information: https://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
namespace AtpCore\Api\Aws;

use AtpCore\BaseClass;
use Aws\CloudWatch\CloudWatchClient;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Throwable;

class CloudWatch extends BaseClass
{

    private $config;

    /**
     * Constructor
     *
     * @param string $version
     * @param string $region
     * @param string $awsKey
     * @param string $awsSecret
     */
    public function __construct($version = "latest", $region = "eu-west-1", $awsKey = null, $awsSecret = null)
    {
        // Set config
        $this->config = [
            'version' => $version,
            'region' => $region,
        ];
        if (!empty($awsKey)) {
            $this->config['credentials'] = [
                'key' => $awsKey,
                'secret' => $awsSecret,
            ];
        }

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get alarms in specific state (OK|ALARM|INSUFFICIENT_DATA)
     *
     * @param $stateName
     * @return \Aws\Result|bool
     */
    public function describeAlarms($stateName = null)
    {
        try {
            $client = new CloudWatchClient($this->config);
            if (!empty($stateName)) return $client->describeAlarms(["StateValue"=>$stateName]);
            else return $client->describeAlarms();
        } catch (Throwable $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Get query-results from CloudWatch Logs
     *
     * @param string $logGroupName
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param string $queryString
     * @return false|array
     */
    public function getQueryResults($logGroupName, $startDate, $endDate, $queryString)
    {
        try {
            $client = new CloudWatchLogsClient($this->config);

            // Start query (asynchronous)
            $query = $client->startQuery([
                "logGroupName" => $logGroupName,
                "startTime" => $startDate->getTimestamp() * 1000,
                "endTime" => $endDate->getTimestamp() * 1000,
                "queryString" => $queryString,
            ]);

            // Wait until query is completed
            $status = "Running";
            while ($status == "Running") {
                sleep(1);
                $queryResult = $client->getQueryResults(["queryId"=>$query["queryId"]]);
                $status = $queryResult["status"];
            }

            if ($status == "Complete") {
                // Parse results
                $output = [];
                foreach ($queryResult["results"] as $row) {
                    $result = [];
                    foreach ($row as $data) {
                        $result[$data["field"]] = $data["value"];
                    }
                    $output[] = $result;
                }

                // Return
                return $output;
            } else {
                $this->setMessages("Query not completed: $status");
                return false;
            }
        } catch (Throwable $e) {
            $this->setMessages("Query failed: {$e->getMessage()}");
            $this->setErrorData($e->getTraceAsString());
            return false;
        }
    }

}
