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
     * Compose pdf-attachment by template (and variables)
     *
     * @param string $templatePath
     * @param string $templateFile
     * @param array $templateVariables
     * @return bool|string
     */
    public function composePDF($templatePath, $templateFile, $templateVariables = [])
    {
        // Set paths (template)
        $paths = [
            getcwd(). $templatePath,
        ];

        // Setup twig-template
        $templateLoader = new FilesystemLoader($paths);
        $templateWrapper = new Environment($templateLoader);
        $templateWrapper->getExtension(\Twig\Extension\CoreExtension::class)->setNumberFormat(0, ',', '.');
        try {
            $template = $templateWrapper->load($templateFile);
        } catch (Throwable $e) {
            $this->setMessages($e->getCode() . ": " . $e->getMessage());
            return false;
        }

        // Compose content by template
        $content = $template->render($templateVariables);

        // Generate PDF-document
        $pdfCreator = new \AtpCore\File\PDF();
        $pdfContent = $pdfCreator->generate($content, ["isRemoteEnabled"=>true, "chroot"=>\Api\Module::ROOT_DIR . "public"]);

        // Return
        return $pdfContent;
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
     * @return bool|null
     */
    public function validateEmailAddress($emailAddress)
    {
        // Check format email-address
        if (filter_var($emailAddress, FILTER_VALIDATE_EMAIL) === false) {
            $this->addMessage("Invalid emailaddress");
            return false;
        }

        // Validate email address (Mailgun)
        $valid = true;
        try {
            $result = $this->mailgunEU->emailValidationV4()->validate($emailAddress, true);
            switch ($result->getResult()) {
                case "catch_all": // The validity of the recipient address cannot be determined as the provider accepts any and all email regardless of whether or not the recipient’s mailbox exists.
                case "deliverable": // The recipient address is considered to be valid and should accept email.
                    break;
                case "do_not_send": // The recipient address is considered to be highly risky and will negatively impact sending reputation if sent to.
                    $this->addMessage("Invalid emailaddress (high risk)");
                    $valid = false;
                    break;
                case "undeliverable": // The recipient address is considered to be invalid and will result in a bounce if sent to.
                    $this->addMessage("Invalid emailaddress (undeliverable)");
                    $valid = false;
                    break;
                case "unknown": // The validity of the recipient address cannot be determined for a variety of potential reasons. Please refer to the associated ‘reason’ array returned in the response.
                    foreach ($result->getReason() AS $reason) {
                        switch ($reason) {
                            case "failed custom grammar check": // The mailbox failed our custom ESP local-part grammar check.
                            case "high_risk_domain": // Information obtained about the domain indicates it is high risk to send email to.
                            case "no_mx": // The recipient domain does not have a valid MX host.
                            case "No MX host found": // The recipient domain does not have a valid MX host. Note: this reason will be consolidated to only “no_mx” in the future.
                            case "no_mx / No MX host found": // The recipient domain does not have a valid MX host. Note: this reason will be consolidated to only “no_mx” in the future.
                            case "mailbox_does_not_exist": // The mailbox is undeliverable or does not exist.
                            case "mailbox_is_disposable_address": // The mailbox has been identified to be a disposable address. Disposable address are temporary, generally one time use, addresses.
                            case "tld_risk": // The domain has a top-level-domain (TLD) that has been identified as high risk.
                            case "unknown_provider": // The MX provider is an unknown provider.
                                $this->addMessage("Invalid emailaddress ($reason)");
                                $valid = false;
                                break;
                            case "catch_all": // The validity of the recipient address cannot be determined as the provider accepts any and all email regardless of whether or not the recipient’s mailbox exists.
                            case "immature_domain": // The domain is newly created based on the WHOIS information.
                            case "long_term_disposable": // The mailbox has been identified as a long term disposable address. Long term disposable addresses can be quickly and easily deactivated by users, but they will not expire without user intervention.
                            case "mailbox_is_role_address": // The mailbox is a role based address (ex. support@…, marketing@…).
                            case "subdomain_mailer": // The recipient domain is identified to be a subdomain and is not on our exception list. Subdomains are considered to be high risk as many spammers and malicious actors utilize them.
                                break;
                            default:
                                $this->addMessage("Invalid emailaddress ($reason)");
                                $valid = false;
                                break;
                        }
                    }
                    break;
            }
        } catch (\Exception $exception) {
            $this->setMessages("{$exception->getCode()}: {$exception->getMessage()}");
            $this->setMessages($exception->getMessage());
            $valid = null;
        }

        // Return
        return $valid;
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