<?php

namespace AtpCore\Communication;

use Exception;
use AtpCore\Api\OneSignal\Api;
use AtpCore\Api\OneSignal\Entity\Notification;

class PushNotification
{

    private $config;
    private $messages;
    private $errorData;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config)
    {
        // Set config
        $this->config = $config;

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
     * @param array|string $messages
     */
    public function setMessages($messages)
    {
        if (!is_array($messages)) $messages = [$messages];
        $this->messages = $messages;
    }

    /**
     * Add error-message
     *
     * @param string|array $message
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
     * Send push-notification to iOS/Android device
     *
     * @param string $platform
     * @param string|array $tokens
     * @param string $message
     * @param array $options
     * @return bool
     * @throws Exception
     */
    public function send($platform, $tokens, $message, $options = NULL)
    {
        if (strtolower($platform) == 'onesignal') {
            // Set receiver (overwrite from config)
            if (!empty($this->config['oneSignal']['default_to'])) {
                $tokens = [$this->config['oneSignal']['default_to']];
            }

            // Convert token to array
            if (!is_array($tokens) && is_string($tokens)) $tokens = [$tokens];

            // Initialize client
            $client = new Api($this->config['oneSignal']['host'], $this->config['oneSignal']['apiKey']);

            // Setup notification
            $notificationFields = [
                'app_id' => $this->config['oneSignal']['appId'],
                'include_player_ids' => $tokens,
                'data' => $options,
                'contents' => ["en"=>$message, "nl"=>$message]
            ];
            $notification = new Notification($notificationFields);

            // Send notification
            $result = $client->send($notification);
            if ($result === false) {
                $this->setErrorData($client->getErrorData());
                $this->setMessages($client->getMessages());
            }

            // Return
            return $result;
        } else {
            $this->addMessage("Unknown platform");
            return false;
        }
    }

}