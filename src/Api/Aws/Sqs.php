<?php
/**
 * API-information: https://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
namespace AtpCore\Api\Aws;

use AtpCore\BaseClass;
use Exception;
use Aws\Sqs\SqsClient;

class Sqs extends BaseClass
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
        $this->client = new SqsClient($this->config);

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get list of queues
     *
     * @return \Aws\Result
     */
    public function getQueueList()
    {
        return $this->client->listQueues();
    }

    /**
     * Get queue-details by name
     *
     * @param string $queueName
     * @return bool|\Aws\Result
     */
    public function getQueue($queueName)
    {
        try {
            return $this->client->getQueueUrl(["QueueName"=>$queueName]);
        } catch (Exception $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Get queue-attributes by name
     *
     * @param string $queueName
     * @param array $attributes
     * @return bool|\Aws\Result
     */
    public function getQueueAttributes($queueName, $attributes)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        if ($queueUrl !== false) {
            try {
                // Return
                return $this->client->getQueueAttributes(["QueueUrl"=>$queueUrl, "AttributeNames"=>$attributes]);
            } catch (Exception $e) {
                // Return
                $this->setErrorData($e->getMessage());
                return false;
            }
        } else {
            // Return
            return false;
        }
    }

    /**
     * Get message from queue
     *
     * @param string $queueName
     * @param int $maxMessages
     * @return bool|array
     */
    public function getQueueMessages($queueName, $maxMessages = 10)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        if ($queueUrl !== false) {
            try {
                $result = $this->client->receiveMessage(
                    [
                        'AttributeNames' => ['SentTimestamp'],
                        'MaxNumberOfMessages' => $maxMessages,
                        'MessageAttributeNames' => ['All'],
                        'QueueUrl' => $queueUrl,
                        'WaitTimeSeconds' => 0,
                    ]
                );

                // Return
                return $result->get('Messages');
            } catch (Exception $e) {
                // Return
                $this->setErrorData($e->getMessage());
                return false;
            }
        } else {
            // Return
            return false;
        }
    }

    /**
     * Get URL for specific queue

     * @param string $queueName
     * @return bool|string
     */
    public function getQueueUrl($queueName)
    {
        $queue = $this->getQueue($queueName);
        if ($queue === false) return false;
        else return $queue->get("QueueUrl");
    }

    /**
     * Delete message from queue
     *
     * @param string $queueName
     * @param string $receiptHandle
     */
    public function deleteQueueMessage($queueName, $receiptHandle)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        if ($queueUrl !== false) {
            $this->client->deleteMessage(
                [
                    'QueueUrl' => $queueUrl,
                    'ReceiptHandle' => $receiptHandle
                ]
            );
        }
    }

    /**
     * Send message to queue
     *
     * @param string $queueName
     * @param string|array $message
     * @return bool
     */
    public function sendMessage($queueName, $message)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        if ($queueUrl !== false) {
            $sqsMessage = [];
            $sqsMessage["QueueUrl"] = $queueUrl;
            $sqsMessage["MessageBody"] = (is_array($message)) ? json_encode($message) : $message;

            try {
                $this->client->sendMessage($sqsMessage);
                return true;
            } catch (Exception $e) {
                $this->setErrorData($e->getMessage());
                return false;
            }
        } else {
            return false;
        }
    }
}
