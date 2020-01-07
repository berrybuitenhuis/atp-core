<?php
/**
 * API-information: https://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
namespace AtpCore\Api\Aws;

use AtpCore\BaseClass;
use Aws\CloudWatch\CloudWatchClient;
use Throwable;

class CloudWatch extends BaseClass
{

    private $client;
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

        // Set client
        $this->client = new CloudWatchClient($this->config);

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get alarms in specific state (OK|ALARM|INSUFFICIENT_DATA)
     * @param $stateName
     * @return \Aws\Result|bool
     */
    public function describeAlarms($stateName = null)
    {
        try {
            if (!empty($stateName)) return $this->client->describeAlarms(["StateValue"=>$stateName]);
            else return $this->client->describeAlarms();
        } catch (Throwable $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

}
