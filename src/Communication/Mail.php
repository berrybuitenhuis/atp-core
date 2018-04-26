<?php

namespace AtpCore\Communication;

use Mailgun\Mailgun;

class Mail {

    private $config;
    private $messages;
    private $errorData;

    /**
     * Constructor
     *
     * @param string $type
     * @param array $config
     */
    public function __construct($config)
    {
        // Set mail-config
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

    public function send($domain, $from, $to, $subject, $text)
    {
        $result = $this->sendMailgun($domain, $from, $to, $subject, $text);
        return $result;
    }

    private function sendMailgun($domain, $from, $to, $subject, $text)
    {
        // Initialize Mailgun
        $mailgun = Mailgun::create($this->config['mailgun']['api_key']);

        // Compose/send message
        $mailgun->messages()->send($domain, [
            'from'    => $from,
            'to'      => $to,
            'subject' => $subject,
            'text'    => $text
        ]);
    }
}

?>