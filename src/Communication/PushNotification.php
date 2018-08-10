<?php

namespace AtpCore\Communication;

use ZendService\Apple\Apns\Client\Message as ApnsClient;
use ZendService\Apple\Apns\Message as ApnsMessage;
use ZendService\Apple\Apns\Response\Message as ApnsResponse;
use ZendService\Apple\Exception\RuntimeException as ApnsRuntimeException;

use ZendService\Google\Gcm\Client as GcmClient;
use ZendService\Google\Gcm\Message as GcmMessage;
use ZendService\Google\Exception\RuntimeException as GcmRuntimeException;

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
     * @param string $platform
     * @param string $token
     * @param string $message
     * @param array $options
     * @return bool
     */
    public function send($platform = 'ios', $token, $message, $options = NULL)
    {
        $config = $this->config['push_notification'];

        if (strtolower($platform) == 'ios') {
            $host = ($config['ios']['host'] == 'production') ? ApnsClient::PRODUCTION_URI : ApnsClient::SANDBOX_URI;
            $certificate = $config['ios']['certificatePath'];
            $password = $config['ios']['certificatePassword'];

            $client = new ApnsClient();
            $client->open($host, $certificate, $password);

            $uniqueId = mt_rand(1,9999);
            $msg = new ApnsMessage();
            $msg->setId($uniqueId);
            $msg->setToken($token);
            $msg->setBadge(1);
            //$msg->setSound('bingbong.aiff');
            $msg->setAlert($message);
            $msg->setCustom(array("extra_data"=>$options));

            try {
                $response = $client->send($msg);
                $client->close();
                if ($response->getCode() != ApnsResponse::RESULT_OK) {
                    switch ($response->getCode()) {
                        case ApnsResponse::RESULT_PROCESSING_ERROR:
                            // you may want to retry
                            $this->addMessage('RESULT_PROCESSING_ERROR');
                            return false;
                        case ApnsResponse::RESULT_MISSING_TOKEN:
                            // you were missing a token
                            $this->addMessage('RESULT_MISSING_TOKEN');
                            return false;
                        case ApnsResponse::RESULT_MISSING_TOPIC:
                            // you are missing a message id
                            $this->addMessage('RESULT_MISSING_TOPIC');
                            return false;
                        case ApnsResponse::RESULT_MISSING_PAYLOAD:
                            // you need to send a payload
                            $this->addMessage('RESULT_MISSING_PAYLOAD');
                            return false;
                        case ApnsResponse::RESULT_INVALID_TOKEN_SIZE:
                            // the token provided was not of the proper size
                            $this->addMessage('RESULT_INVALID_TOKEN_SIZE');
                            return false;
                        case ApnsResponse::RESULT_INVALID_TOPIC_SIZE:
                            // the topic was too long
                            $this->addMessage('RESULT_INVALID_TOPIC_SIZE');
                            return false;
                        case ApnsResponse::RESULT_INVALID_PAYLOAD_SIZE:
                            // the payload was too large
                            $this->addMessage('RESULT_INVALID_PAYLOAD_SIZE');
                            return false;
                        case ApnsResponse::RESULT_INVALID_TOKEN:
                            // the token was invalid; remove it from your system
                            $this->addMessage('RESULT_INVALID_TOKEN');
                            return false;
                        case ApnsResponse::RESULT_UNKNOWN_ERROR:
                            // apple didn't tell us what happened
                            $this->addMessage('RESULT_UNKNOWN_ERROR');
                            return false;
                    }
                } else {
                    return true;
                }
            } catch (ApnsRuntimeException $e) {
                $client->close();
                $this->addMessage($e->getMessage());
                return false;
            }
        } elseif (strtolower($platform) == 'android') {
            $client = new GcmClient();

            $tmp = new \Zend\Http\Client(null, array(
                'adapter' => 'Zend\Http\Client\Adapter\Socket',
                'sslverifypeer' => false
            ));
            $client->setHttpClient($tmp);
            $client->setApiKey($config['android']['apiKey']);

            $msg = new GcmMessage();

            // up to 100 registration ids can be sent to at once
            $msg->setRegistrationIds(array($token));

            // optional fields
            $msg->setData(array(
                'message' => $message,
                'extra_data' => $options,
            ));
            $msg->setDelayWhileIdle(false);
            $msg->setTimeToLive(600);
            $msg->setDryRun(false);

            try {
                $response = $client->send($msg);
            } catch (GcmRuntimeException $e) {
                $this->addMessage($e->getMessage());
                return false;
            }

            if ($response->getSuccessCount() == 1) {
                return true;
            } else {
                return false;
            }
        } elseif (strtolower($platform) == 'onesignal') {
            $notificationFields = array(
                'app_id' => $config['oneSignal']['appId'],
                'include_player_ids' => array($token),
                'data' => $options,
                'contents' => array("en"=>$message, "nl"=>$message)
            );
            $notification = new \AtpCore\Api\OneSignal\Entity\Notification($notificationFields);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['oneSignal']['host'] . "notifications");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                'Authorization: Basic ' . $config['oneSignal']['apiKey']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $notification->encode());
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

            $response = curl_exec($ch);
            curl_close($ch);

            $response = json_decode($response);
            if (isset($response->recipients) && $response->recipients == 1) {
                return true;
            } else {
                $this->addMessage(current($response->errors));
                return false;
            }
        }
    }

}