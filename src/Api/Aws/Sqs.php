<?php
/**
 * API-information: https://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
namespace AtpCore\Api\Aws;

class Sqs
{

    private $config;
    private $messages;
    private $errorData;

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
        $this->client = new \Aws\Sqs\SqsClient($this->config);

        // Set error-messages
        $this->messages = array();
        $this->errorData = array();
    }

    /**
     * Set error-data
     *
     * @param $data
     * @return array
     */
    public function setErrorData($data)
    {
        $this->errorData = $data;
    }

    /**
     * Get error-data
     *
     * @return array
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * Set error-message
     *
     * @param array $messages
     */
    public function setMessages($messages)
    {
        if (!is_array($messages)) $messages = array($messages);
        $this->messages = $messages;
    }

    /**
     * Add error-message
     *
     * @param array $message
     */
    public function addMessage($message)
    {
        if (!is_array($message)) $message = array($message);
        $this->messages = array_merge($this->messages, $message);
    }

    /**
     * Get error-messages
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get list of queues
     */
    public function getQueueList()
    {
        return $this->client->listQueues();
    }

    /**
     * Get queue-details by name
     * @param string $queueName
     */
    public function getQueue($queueName)
    {
        try {
            return $this->client->getQueueUrl(["QueueName"=>$queueName]);
        } catch (\Exception $e) {
            $this->setErrorData($e->getMessage());
            return false;
        }
    }

    /**
     * Get URL for specific queue
     * @param string $queueName
     */
    public function getQueueUrl($queueName)
    {
        $queue = $this->getQueue($queueName);
        if ($queue === false) return false;
        else return $queue->get("QueueUrl");
    }

    /**
     * Send message to queue
     * @param string $queueName
     * @param string $message
     */
    public function sendMessage($queueName, $message)
    {
        $queueUrl = $this->getQueueUrl($queueName);
        if ($queueUrl !== false) {
            $message = [
                            "QueueUrl" => $queueUrl,
                            "MessageBody" => $message
            ];

            try {
                $this->client->sendMessage($message);
                return true;
            } catch (\Exception $e) {
                $this->setErrorData($e->getMessage());
                return false;
            }
        } else {
            return false;
        }
    }
}
