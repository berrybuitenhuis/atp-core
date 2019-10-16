<?php
/**
 * API-information: https://docs.aws.amazon.com/aws-sdk-php/v3/api/
 */
namespace AtpCore\Api\Aws;

use Aws\Ec2\Ec2Client;

class Ec2
{

    private $client;
    private $config;
    private $instanceId;
    private $instanceSecurityGroups;
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
        $this->client = new Ec2Client($this->config);
        $this->instanceId = $this->getInstanceId();
        if ($this->instanceId !== false) {
            $this->instanceSecurityGroups = $this->getInstanceSecurityGroups();
        }

        // Set error-messages
        $this->messages = [];
        $this->errorData = [];
    }

    /**
     * Set error-data
     *
     * @param $data
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
        if (!is_array($messages)) $messages = [$messages];
        $this->messages = $messages;
    }

    /**
     * Add error-message
     *
     * @param array $message
     */
    public function addMessage($message)
    {
        if (!is_array($message)) $message = [$message];
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
     * Get EC2 instance-id of current machine
     *
     * @return string|boolean
     */
    public function getInstanceId()
    {
        // Documentation: https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/ec2-instance-metadata.html
        $result = @file_get_contents('http://169.254.169.254/' . $this->config['version'] . '/meta-data/instance-id');
        return $result;
    }

    /**
     * Get EC2 security-groups of current machine
     *
     * @return array|boolean
     */
    public function getInstanceSecurityGroups()
    {
        // Documentation: https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/ec2-instance-metadata.html
        $result = @file_get_contents('http://169.254.169.254/' . $this->config['version'] . '/meta-data/security-groups');
        if ($result !== false) {
            // Explode security-groups array (by new-lines)
            $result = explode("\n", $result);
            sort($result);
        }

        // Return
        return $result;
    }

    /**
     * Get all EC2 instances
     *
     * @param boolean $runningCheck
     * @param boolean $securityGroupsCheck
     * @return array
     */
    public function getInstances($runningCheck = true, $securityGroupsCheck = true)
    {
        // Set instances-array
        $instances = [];

        // Get instances (array) of AWS-account
        $instancesArray = $this->client->describeInstances()->toArray();

        // Iterate reservations (instances)
        foreach ($instancesArray['Reservations'] AS $reservation) {
            foreach ($reservation['Instances'] AS $instance) {
                // Get security-groups of instance
                $securityGroups = array_column($instance["SecurityGroups"], 'GroupName');
                sort($securityGroups);

                // Check if security-group matches (if filtering enabled), else skip
                if ($securityGroupsCheck && $securityGroups != $this->getInstanceSecurityGroups()) continue;
                // Check if instance is running (if filtering enabled), else skip
                if ($runningCheck && $instance['State']['Name'] != "running") continue;

                // Add instance to instances-array
                $instances[] = $instance['InstanceId'];
            }
        }

        // Return
        sort($instances);
        return $instances;
    }

    /**
     * Check if instance if allowed for running script (i.e. cronjob)
     */
    public function allowRunningScript()
    {
        // Check if instance-id available
        if ($this->instanceId == false) {
            return true;
        }

        // Get running instances in load-balancer (same security-group)
        $availableInstances = $this->getInstances(true, true);

        // Check if instance is allowed for running scripts
        if ($availableInstances[0] == $this->instanceId) {
            return true;
        } else {
            return false;
        }
    }
}