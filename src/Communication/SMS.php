<?php

namespace AtpCore\Communication;

use AtpCore\BaseClass;
use Exception;

class SMS extends BaseClass
{

    private $client;

    /**
     * Constructor
     *
     * @param array $config
     * @param bool $debug
     */
    public function __construct($config, $debug = false)
    {
        // Set client
        $this->client = new \AtpCore\Api\MessageBird\SMS($config['messageBird']['apiKey'], $debug);

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Send SMS-message
     *
     * @param int $recipient
     * @param string $body
     * @param string $originator
     * @return bool
     * @throws Exception
     */
    public function send($recipient, $body, $originator = null)
    {
        // Send SMS-message
        $result = $this->client->send($recipient, $body, $originator);
        if ($result === false) {
            $this->setErrorData($this->client->getErrorData());
            $this->setMessages($this->client->getMessages());
        }

        // Return
        return $result;
    }

}