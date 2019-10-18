<?php

namespace AtpCore\Communication;

use AtpCore\BaseClass;
use Exception;
use AtpCore\Api\OneSignal\Api;
use AtpCore\Api\OneSignal\Entity\Notification;

class PushNotification extends BaseClass
{

    private $config;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config)
    {
        // Set config
        $this->config = $config;

        // Reset error-messages
        $this->resetErrors();
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