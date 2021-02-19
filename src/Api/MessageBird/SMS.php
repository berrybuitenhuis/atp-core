<?php
/**
 * API-information: https://developers.messagebird.com/api/sms-messaging/#sms-api
 */
namespace AtpCore\Api\MessageBird;

use AtpCore\BaseClass;
use MessageBird\Client;
use MessageBird\Objects\Message;
use MessageBird\Resources\Balance;
use Throwable;

class SMS extends BaseClass
{

    private $client;
    private $debug;

    /**
     * Constructor
     *
     * @param string $apiKey
     * @param bool $debug
     */
    public function __construct($apiKey = null, $debug = false)
    {
        // Set client
        $this->client = new Client($apiKey);

        // Set debug
        $this->debug = $debug;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Check account-balance
     *
     * @return Balance|bool
     */
    public function checkAccountBalance()
    {
        try {
            $balance = $this->client->balance->read();
            return $balance;
        } catch (Throwable $e) {
            $this->setMessages($e->getMessage());
            $this->setErrorData($e);
            return false;
        }
    }

    /**
     * Send SMS
     *
     * @param int $recipient
     * @param string $body
     * @param string $originator
     * @return bool
     */
    public function send($recipient, $body, $originator = null)
    {
        $message = new Message();
        $message->type = Message::TYPE_SMS;
        $message->originator = $originator;
        $message->recipients = [$recipient];
        $message->body = $body;

        try {
            $result = $this->client->messages->create($message);

            // Check status message
            $item = current($result->recipients['items']);
            if ($item->status == "delivery_failed") {
                $this->setErrorData($item);
                $this->setMessages("Message failed ({$item->status}, {$item->statusReason})");
                return false;
            }

            // Return
            return true;
        } catch (Throwable $e) {
            $this->setMessages($e->getMessage());
            $this->setErrorData($e);
            return false;
        }
    }
}