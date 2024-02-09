<?php
/**
 * API-information: https://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
namespace AtpCore\Api\Aws;

use AtpCore\BaseClass;
use Aws\Sns\SnsClient;
use Throwable;

class Sns extends BaseClass
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
        $this->client = new SnsClient($this->config);

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     *
     */
    public function getTopicList()
    {
        try {
            return $this->client->listTopics();
        } catch (Throwable $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    public function getTopicArn($name)
    {
        try {
            $list  = $this->getTopicList()->toArray();
            foreach ($list['Topics'] AS $topic) {
                if (empty($name) || !preg_match("/$name$/", $topic['TopicArn'])) continue;
                return $topic['TopicArn'];
            }
        } catch (Throwable $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Send message to topic
     *
     * @param string $topicName
     * @param string $subject
     * @param mixed $message
     * @param array|null $attributes
     * @return bool
     */
    public function sendMessage($topicName, $subject, $message, $attributes = null)
    {
        // Compose SNS-message
        $snsMessage = [
            'TopicArn' => $this->getTopicArn($topicName),
            'Subject' => $subject,
            'Message' => json_encode($message),
        ];
        if (!empty($attributes)) {
            $snsMessage['MessageAttributes'] = [];
            foreach ($attributes AS $name => $value) {
                $snsMessage['MessageAttributes'][$name] = [
                    'DataType' => 'String',
                    'StringValue' => $value,
                ];
            }
        }

        // Pubish SNS-message
        try {
            $result = $this->client->publish($snsMessage);
            return true;
        } catch (Throwable $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }
}
