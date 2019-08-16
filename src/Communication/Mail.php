<?php

namespace AtpCore\Communication;

use Mailgun\Mailgun;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\ArrayLoader;
use Twig\Environment;

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
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function composeMessage($templatePath, $templateFile, $templateVariables = [])
    {
        // Setup twig-template
        $loader = new FilesystemLoader(getcwd(). $templatePath);
        $twig = new Environment($loader);
        $template = $twig->load($templateFile);

        // Compose message by template
        $message = $template->render($templateVariables);

        // Return
        return $message;
    }

    /**
     * Compose text
     *
     * @param string $text
     * @param array $variables
     * @return string
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function composeText($text, $variables = [])
    {
        // Setup twig-loader
        $loader = new ArrayLoader(['text' => $text]);
        $twig = new Environment($loader);

        // Compose text
        $text = $twig->render('text', $variables);

        // Return
        return $text;
    }

    /**
     * Send mail
     *
     * @param string $from
     * @param string $from_alternative if from-address not active
     * @param string|array $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @return boolean
     */
    public function send($from, $from_alternative, $to, $subject, $text, $html = null)
    {
        $result = $this->sendMailgun($from, $from_alternative, $to, $subject, $text, $html);
        return $result;
    }

    /**
     * Send mail by Mailgun
     *
     * @param string $from
     * @param string $from_alternative if from-address not active
     * @param string|array $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @return boolean
     */
    private function sendMailgun($from, $from_alternative, $to, $subject, $text, $html = null)
    {
        // Check email-addresses (from, to)
        if (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (from)");
            return false;
        } elseif (!empty($from_alternative) && filter_var($from_alternative, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (from_alternative)");
            return false;
        } elseif (!is_array($to) && filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (to)");
            return false;
        } elseif (is_array($to)) {
            foreach ($to AS $recipient) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addMessage("Invalid emailaddress (to)");
                    return false;
                }
            }
        }

        // Initialize Mailgun
        $mailgun = Mailgun::create($this->config['mailgun']['api_key']);

        // Set sender (overwrite from config)
        if (!empty($this->config['mailgun']['default_from'])) {
            $from = $this->config['mailgun']['default_from'];
        } else {
            $tmp = explode("@", $from);

            // Check if sender-mailaddress is verified (by DNS - https://documentation.mailgun.com/en/latest/quickstart-sending.html#verify-your-domain)
            try {
                $domain = $mailgun->domains()->show($tmp[1]);
                if ($domain->getDomain()->getState() != 'active') {
                    $from = $from_alternative;
                }
            } catch (\Exception $e) {
                $from = $from_alternative;
            }
        }

        // Set receiver (overwrite from config)
        if (!empty($this->config['mailgun']['default_to'])) {
            if (is_array($to)) $subject .= " [" . implode(", ", $to) . "]";
            else $subject .= " [" . $to . "]";
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
                'to'      => (is_array($to)) ? implode(",", $to) : $to,
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