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

    public function send($from, $to, $subject, $text)
    {
        $result = $this->sendMailgun($from, $to, $subject, $text);
        return $result;
    }

    private function sendMailgun($from, $to, $subject, $text)
    {
        // Check email-addresses (from, to)
        if (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (from)");
            return false;
        } elseif (filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (to)");
            return false;
        }

        // Initialize Mailgun
        $mailgun = Mailgun::create($this->config['mailgun']['api_key']);

        // Set receiver (overwrite from config)
        if (!empty($this->config['mailgun']['default_to'])) {
            $to = $this->config['mailgun']['default_to'];
        }

        // Set sender-domain
        $domain = substr($from, strrpos($from, '@') + 1);

        // Check sender-domain is activated (verified) for Mailgun
        try {
            // Get domain-item by Mailgun
            $domainItem = $mailgun->domains()->show($domain);

            // Get state of domain, if not active (not verified) unset FROM-address to default
            if ($domainItem->getDomain()->getState() != 'active') {
                $from = $this->config['mailgun']['default_from'];
                $domain = substr($from, strrpos($from, '@') + 1);
            }
        } catch (\Mailgun\Exception\HttpClientException $e) {
            $from = $this->config['mailgun']['default_from'];
            $domain = substr($from, strrpos($from, '@') + 1);
        }

        // Compose/send message
        try {
            $mailgun->messages()->send($domain, [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'text'    => $text
            ]);
        } catch (\Mailgun\Exception\HttpClientException $e) {
            $this->addMessage($e->getResponseBody());
            return false;
        }

        // Return
        return true;
    }
}

?>