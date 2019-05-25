<?php

namespace AtpCore\Communication;

class PushNotification {

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
     * Send push-notification to iOS/Android device
     *
     * @param string $platform
     * @param string|array $tokens
     * @param string $message
     * @param array $options
     * @return bool
     */
    public function send($platform = 'ios', $tokens, $message, $options = NULL)
    {
        if (strtolower($platform) == 'onesignal') {
            // Set receiver (overwrite from config)
            if (!empty($this->config['oneSignal']['default_to'])) {
                $tokens = array($this->config['oneSignal']['default_to']);
            }

            // Convert token to array
            if (!is_array($tokens) && is_string($tokens)) $tokens = array($tokens);

            // Initialize client
            $client = new \AtpCore\Api\OneSignal\Api($this->config['oneSignal']['host'], $this->config['oneSignal']['apiKey']);

            // Setup notification
            $notificationFields = array(
                'app_id' => $this->config['oneSignal']['appId'],
                'include_player_ids' => $tokens,
                'data' => $options,
                'contents' => array("en"=>$message, "nl"=>$message)
            );
            $notification = new \AtpCore\Api\OneSignal\Entity\Notification($notificationFields);

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