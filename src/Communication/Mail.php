<?php

namespace AtpCore\Communication;

use Mailgun\Mailgun;
use Twig_Loader_Filesystem;
use Twig_Environment;

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

    /**
     * Compose mail-message by template (and variables)
     *
     * @param string $templatePath
     * @param string $templateFile
     * @param array $templateVariables
     * @return string
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function composeMessage($templatePath, $templateFile, $templateVariables = [])
    {
        // Setup twig-template
        $loader = new Twig_Loader_Filesystem(getcwd(). $templatePath);
        $twig = new Twig_Environment($loader);
        $template = $twig->load($templateFile);

        // Compose message by template
        $message = $template->render($templateVariables);

        // Return
        return $message;
    }

    /**
     * Send mail
     *
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @return boolean
     */
    public function send($from, $to, $subject, $text, $html = null)
    {
        $result = $this->sendMailgun($from, $to, $subject, $text, $html);
        return $result;
    }

    /**
     * Send mail by Mailgun
     *
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @return boolean
     */
    private function sendMailgun($from, $to, $subject, $text, $html = null)
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

        // Set sender (overwrite from config)
        if (!empty($this->config['mailgun']['default_from'])) {
            $from = $this->config['mailgun']['default_from'];
        }

        // Set receiver (overwrite from config)
        if (!empty($this->config['mailgun']['default_to'])) {
            $subject .= " [" . $to . "]";
            $to = $this->config['mailgun']['default_to'];
        }

        // Set sender-domain
        $domain = substr($from, strrpos($from, '@') + 1);

        // Check sender-domain is activated (verified) for Mailgun
        try {
            // Get domain-item by Mailgun
            $domainItem = $mailgun->domains()->show($domain);

            // Get state of domain, if not active (not verified) unset FROM-address to fallback-sender
            if ($domainItem->getDomain()->getState() != 'active') {
                $from = $this->config['mailgun']['fallback_from'];
                $domain = substr($from, strrpos($from, '@') + 1);
            }
        } catch (\Mailgun\Exception\HttpClientException $e) {
            $from = $this->config['mailgun']['fallback_from'];
            $domain = substr($from, strrpos($from, '@') + 1);
        }

        // Compose/send message
        try {
            $mailgun->messages()->send($domain, [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'text'    => $text,
                'html'    => $html,
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