<?php

namespace AtpCore\Communication;

use AtpCore\BaseClass;
use Mailgun\Mailgun;
use Throwable;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\ArrayLoader;
use Twig\Environment;

class Mail extends BaseClass
{

    private $config;
    private $debug;
    private $mailgunEU;
    private $mailgunUS;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config, $debug = false)
    {
        // Set mail-config
        $this->config = $config;

        // Set debug-flag
        $this->debug = $debug;

        // ReSet error-messages
        $this->resetErrors();

        // Set mail-client
        $this->mailgunEU = Mailgun::create($this->config['mailgun']['api_key'], "https://api.eu.mailgun.net/");
        $this->mailgunUS = Mailgun::create($this->config['mailgun']['api_key'], "https://api.mailgun.net/");
    }

    /**
     * Check if domain is active
     *
     * @param string $domainName
     * @param string $region
     * @return bool
     */
    public function checkActiveDomain($domainName, $region = "EU")
    {
        try {
            if ($region == "US") $domain = $this->mailgunUS->domains()->show($domainName);
            else $domain = $this->mailgunEU->domains()->show($domainName);
            if ($domain->getDomain()->getState() == 'active') {
                return true;
            } else {
                return false;
            }
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Compose mail-message by template (and variables)
     *
     * @param string $layoutPath
     * @param string $templatePath
     * @param string $templateFile
     * @param array $templateVariables
     * @return bool|string
     */
    public function composeMessage($layoutPath, $templatePath, $templateFile, $templateVariables = [])
    {
        // Set paths (layout/template)
        $paths = [
            getcwd(). $layoutPath,
            getcwd(). $templatePath,
        ];

        // Setup twig-template
        $templateLoader = new FilesystemLoader($paths);
        $templateWrapper = new Environment($templateLoader);
        try {
            $template = $templateWrapper->load($templateFile);
        } catch (Throwable $e) {
            $this->setMessages($e->getCode() . ": " . $e->getMessage());
            return false;
        }

        // Compose message-body by template
        $message = $template->render($templateVariables);

        // Return
        return $message;
    }

    /**
     * Compose text
     *
     * @param string $text
     * @param array $variables
     * @return bool|string
     */
    public function composeText($text, $variables = [])
    {
        // Setup twig-loader
        $loader = new ArrayLoader(['text' => $text]);
        $twig = new Environment($loader);

        // Compose text
        try {
            $text = $twig->render('text', $variables);
        } catch (Throwable $e) {
            $this->setMessages($e->getCode() . ": " . $e->getMessage());
            $text = false;
        }

        // Return
        return $text;
    }

    /**
     * Get events
     *
     * @param \DateTime $startDate
     * @param string $type
     * @param string $region
     * @return array
     */
    public function getEvents($startDate, $type = null, $region = "EU")
    {
        // Get active domains
        $domains = $this->getActiveDomains($region);

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
            if ($region == "US") $events = $this->mailgunUS->events()->get($domain->getName(), $params);
            else $events = $this->mailgunEU->events()->get($domain->getName(), $params);
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
     * @param string $region
     * @return array
     */
    public function getActiveDomains($region = "EU")
    {
        $activeDomains = [];
        if ($region == "US") $domains = $this->mailgunUS->domains()->index();
        else $domains = $this->mailgunEU->domains()->index();
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
     * @param string|array $cc
     * @param string|array $bcc
     * @param string $subject
     * @param string $text
     * @param string $html
     * @param array $attachments
     * @param array $images
     * @return boolean
     */
    public function send($from, $fromAlternative, $to, $cc, $bcc, $subject, $text, $html = null, $attachments = [], $images = [])
    {
        $result = $this->sendMailgun($from, $fromAlternative, $to, $cc, $bcc, $subject, $text, $html, $attachments, $images);
        return $result;
    }

    /**
     * Validate email-address
     * 
     * @param string $emailAddress
     * @return bool
     */
    public function validateEmailAddress($emailAddress)
    {
        // Check format email-address
        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress");
            return false;
        }

        // TODO: Validate email address (Mailgun)

        // Return
        return true;
    }

    /**
     * Send mail by Mailgun
     *
     * @param string $from
     * @param string $fromAlternative if from-address not active
     * @param string|array $to
     * @param string|array $cc
     * @param string|array $bcc
     * @param string $subject
     * @param string $text
     * @param string $html
     * @param array $attachments
     * @param array $images
     * @return boolean
     */
    private function sendMailgun($from, $fromAlternative, $to, $cc, $bcc, $subject, $text, $html = null, $attachments = [], $images = [])
    {
        // Check email-addresses (from, to)
        if (filter_var($from, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (type: from, email: {$from})");
            return false;
        } elseif (!empty($fromAlternative) && filter_var($fromAlternative, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (type: from_alternative, email: {$fromAlternative})");
            return false;
        } elseif (!is_array($to) && filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (type: to, email: {$to})");
            return false;
        } elseif (is_array($to)) {
            foreach ($to AS $recipient) {
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addMessage("Invalid emailaddress (type: to, email: {$recipient})");
                    return false;
                }
            }
        } elseif (!empty($cc) && !is_array($cc) && filter_var($cc, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (type: cc, email: {$cc})");
            return false;
        } elseif (!empty($cc) && is_array($cc)) {
            foreach ($cc AS $recipientCC) {
                if (filter_var($recipientCC, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addMessage("Invalid emailaddress (type: cc, email: {$recipientCC})");
                    return false;
                }
            }
        } elseif (!empty($bcc) && !is_array($bcc) && filter_var($bcc, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress (type: bcc, email: {$bcc})");
            return false;
        } elseif (!empty($bcc) && is_array($bcc)) {
            foreach ($bcc AS $recipientBCC) {
                if (filter_var($recipientBCC, FILTER_VALIDATE_EMAIL) === false) {
                    $this->addMessage("Invalid emailaddress (type: bcc, email: {$recipientBCC})");
                    return false;
                }
            }
        }

        // Set sender (overwrite from config)
        $region = "EU";
        $domainVerified = false;
        if (!empty($this->config['mailgun']['default_from'])) {
            $from = $this->config['mailgun']['default_from'];
        } else {
            try {
                // Check if sender-maildomain is verified/active (by DNS - https://documentation.mailgun.com/en/latest/quickstart-sending.html#verify-your-domain)
                $domainName = substr($from, strrpos($from, '@') + 1);
                $result = $this->checkActiveDomain($domainName);
                if ($result === false) {
                    $result = $this->checkActiveDomain($domainName, "US");
                    if ($result === true) $region = "US";
                }

                if ($result == true) {
                    $domainVerified = true;
                } else {
                    $from = $fromAlternative;
                }
            } catch (Throwable $e) {
                // Check if sender-maildomain not available in mailgun, then replace by alternative-sender
                if ($e->getCode() == 404) {
                    $from = $fromAlternative;
                } else {
                    $this->addMessage($e->getCode() . ": " . $e->getMessage());
                    return false;
                }
            }
        }

        // Set receiver (overwrite from config)
        if (!empty($this->config['mailgun']['default_to'])) {
            if (is_array($to)) $subject .= " [" . implode(", ", $to) . "]";
            else $subject .= " [" . $to . "]";
            $to = $this->config['mailgun']['default_to'];

            if (!empty($cc)) {
                if (is_array($cc)) $subject .= " [CC: " . implode(", ", $cc) . "]";
                else $subject .= " [CC: " . $cc . "]";
                $cc = null;
            }
            if (!empty($bcc)) {
                if (is_array($bcc)) $subject .= " [BCC: " . implode(", ", $bcc) . "]";
                else $subject .= " [BCC: " . $bcc . "]";
                $bcc = null;
            }
        }

        // Set sender-domain
        $domain = substr($from, strrpos($from, '@') + 1);

        // Check sender-domain is activated (verified) for Mailgun (skip if already checked)
        if ($domainVerified === false) {
            try {
                // Check if sender-maildomain is verified/active (by DNS - https://documentation.mailgun.com/en/latest/quickstart-sending.html#verify-your-domain)
                $result = $this->checkActiveDomain($domain);
                if ($result === false) {
                    $result = $this->checkActiveDomain($domain, "US");
                    if ($result === true) $region = "US";
                }

                // Get state of domain, if not active (not verified) unset FROM-address to fallback-sender
                if ($result === false) {
                    $from = $this->config['mailgun']['fallback_from'];
                    $domain = substr($from, strrpos($from, '@') + 1);
                }
            } catch (Throwable $e) {
                // Check if sender-maildomain not available in mailgun, then replace by alternative-sender
                if ($e->getCode() == 404) {
                    $from = $this->config['mailgun']['fallback_from'];
                    $domain = substr($from, strrpos($from, '@') + 1);
                } else {
                    $this->addMessage($e->getCode() . ": " . $e->getMessage());
                    return false;
                }
            }
        }

        // Convert attachment-properties
        if (is_array($attachments)) {
            foreach ($attachments AS $k => $v) {
                if (isset($v["fileContentBase64"])) {
                    $attachments[$k]["fileContent"] = base64_decode($v["fileContentBase64"]);
                    unset($attachments[$k]["fileContentBase64"]);
                }
            }
        }

        // Set message-parameters
        $params = [
            'from'      => $from,
            'to'        => (is_array($to)) ? implode(",", $to) : $to,
            'subject'   => $subject,
            'text'      => $text,
            'html'      => $html,
        ];
        if (!empty($cc)) {
            $params['cc'] = (is_array($cc)) ? implode(",", $cc) : $cc;
        }
        if (!empty($bcc)) {
            $params['bcc'] = (is_array($bcc)) ? implode(",", $bcc) : $bcc;
        }
        if (is_array($attachments) && count($attachments) > 0) {
            $params['attachment'] = $attachments;
        }
        if (is_array($images) && count($images) > 0) {
            $params['inline'] = $images;
        }

        // Send message
        if ($this->debug !== true) {
            try {
                if ($region == "US") $this->mailgunUS->messages()->send($domain, $params);
                else $this->mailgunEU->messages()->send($domain, $params);
            } catch (Throwable $e) {
                $this->addMessage($e->getCode() . ": " . $e->getMessage());
                return false;
            }
        } else {
            if (!isset($params['cc'])) $params['cc'] = "";
            if (!isset($params['bcc'])) $params['bcc'] = "";
            print("Email [{$subject}] sent to: {$params['to']}, cc: {$params['cc']}, bcc: {$params['bcc']}, from: {$from}\n");
        }

        // Return
        return true;
    }
}