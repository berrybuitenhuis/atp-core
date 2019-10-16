<?php

namespace AtpCore\Communication;

use Exception;
use Mailgun\Mailgun;
use Mailgun\Exception\HttpClientException;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\ArrayLoader;
use Twig\Environment;

class Mail
{

    private $config;
    private $messages;
    private $errorData;
    private $mailgun;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config)
    {
        // Set mail-config
        $this->config = $config;

        // Set error-messages
        $this->messages = [];
        $this->errorData = [];

        // Set mail-client
        $this->mailgun = Mailgun::create($this->config['mailgun']['api_key']);
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
     * @param array $messages
     */
    public function setMessages($messages)
    {
        if (!is_array($messages)) $messages = [$messages];
        $this->messages = $messages;
    }

    /**
     * Add error-message
     *
     * @param array|string $message
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
     * Compose mail-message by template (and variables)
     *
     * @param string $templatePath
     * @param string $templateFile
     * @param array $templateVariables
     * @return string
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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
     * Get events
     *
     * @param \DateTime $startDate
     * @param string $type
     * @return array
     */
    public function getEvents($startDate, $type = null)
    {
        // Get active domains
        $domains = $this->getActiveDomains();

        // Set parameters
        $result = [];
        $params = [
            'end' => $startDate->format('r'),
            'limit' => 300,
        ];
        if (!empty($type)) $params['event'] = $type;

        // Iterate domains
        foreach ($domains AS $domain) {
            // Get events by domain/parameters
            $events = $this->mailgun->events()->get($domain->getName(), $params);
            if (count($events->getItems()) > 0) {
                $result[$domain->getName()] = [];
                // Iterate items
                foreach ($events->getItems() AS $item) {
                    // Add item to result
                    $reason = $item->getReason();
                    if (!empty($item->getDeliveryStatus()['message'])) $reason .= " [" . $item->getDeliveryStatus()['message'] . "]";
                    $result[$domain->getName()][$item->getRecipient()] = $reason;
                }
            }
        }

        // Return
        return $result;
    }

    /**
     * Get list of active domains
     *
     * @return array
     */
    public function getActiveDomains()
    {
        $activeDomains = [];
        $domains = $this->mailgun->domains()->index();
        foreach ($domains->getDomains() AS $domain) {
            if ($domain->getState() == 'active') {
                $activeDomains[] = $domain;
            }
        }

        return $activeDomains;
    }

    /**
     * Send mail
     *
     * @param string $from
     * @param string $fromAlternative if from-address not active
     * @param string|array $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @return boolean
     */
    public function send($from, $fromAlternative, $to, $subject, $text, $html = null)
    {
        $result = $this->sendMailgun($from, $fromAlternative, $to, $subject, $text, $html);
        return $result;
    }

    /**
     * Send mail by Mailgun
     *
     * @param string $from
     * @param string $fromAlternative if from-address not active
     * @param string|array $to
     * @param string $subject
     * @param string $text
     * @param string $html
     * @return boolean
     */
    private function sendMailgun($from, $fromAlternative, $to, $subject, $text, $html = null)
    {
        // Check email-addresses (from, to)
        if (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (from)");
            return false;
        } elseif (!empty($fromAlternative) && filter_var($fromAlternative, FILTER_VALIDATE_EMAIL) === false) {
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

        // Set sender (overwrite from config)
        if (!empty($this->config['mailgun']['default_from'])) {
            $from = $this->config['mailgun']['default_from'];
        } else {
            $tmp = explode("@", $from);

            // Check if sender-mailaddress is verified (by DNS - https://documentation.mailgun.com/en/latest/quickstart-sending.html#verify-your-domain)
            try {
                $domain = $this->mailgun->domains()->show($tmp[1]);
                if ($domain->getDomain()->getState() != 'active') {
                    $from = $fromAlternative;
                }
            } catch (Exception $e) {
                $from = $fromAlternative;
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
            $domainItem = $this->mailgun->domains()->show($domain);

            // Get state of domain, if not active (not verified) unset FROM-address to fallback-sender
            if ($domainItem->getDomain()->getState() != 'active') {
                $from = $this->config['mailgun']['fallback_from'];
                $domain = substr($from, strrpos($from, '@') + 1);
            }
        } catch (HttpClientException $e) {
            $from = $this->config['mailgun']['fallback_from'];
            $domain = substr($from, strrpos($from, '@') + 1);
        }

        // Compose/send message
        try {
            $this->mailgun->messages()->send(
                $domain,
                [
                    'from'    => $from,
                    'to'      => (is_array($to)) ? implode(",", $to) : $to,
                    'subject' => $subject,
                    'text'    => $text,
                    'html'    => $html,
                ]
            );
        } catch (HttpClientException $e) {
            $this->addMessage($e->getResponseBody());
            return false;
        }

        // Return
        return true;
    }
}