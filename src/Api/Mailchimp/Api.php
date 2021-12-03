<?php

/**
 * API-information: https://mailchimp.com/developer/marketing/docs/fundamentals/
 */
namespace AtpCore\Api\Mailchimp;

use AtpCore\BaseClass;
use MailchimpMarketing\ApiClient;

class Api extends BaseClass
{

    private $client;

    /**
     * Constructor
     *
     * @param string $dc
     * @param string $apiKey
     * @param boolean $debug
     */
    public function __construct($dc, $apiKey, $debug = false)
    {
        // Set client
        $this->client = new ApiClient();
        $this->client->setConfig([
            'apiKey' => $apiKey,
            'server' => $dc,
        ]);

        // Set debug
        if ($debug) $this->client->setDebug($debug);

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get member-info in list
     *
     * @param string $emailAddress
     * @param string $listId
     * @return bool
     */
    public function getMemberInfo($emailAddress, $listId) {
        // Get member-info
        try {
            $member = $this->client->lists->getListMember($listId, $this->getSubscriberHash($emailAddress));
        } catch (\Exception $ex) {
            $this->setErrorData($ex->getTrace());
            $this->setMessages($ex->getCode() . ": " . $ex->getMessage());
            return false;
        }

        // Return
        return $member;
    }

    /**
     * Update member-tag in list
     *
     * @param string $emailAddress
     * @param string $listId
     * @param string $name
     * @param string $status
     * @return bool
     */
    public function updateMemberTag($emailAddress, $listId, $name, $status)
    {
        // Set member-tag (body)
        $body = [
            "tags" => [
                [
                    "name" => $name,
                    "status" => $status
                ],
            ],
            "is_syncing" => false
        ];

        // Update member-tag
        try {
            $this->client->lists->updateListMemberTags($listId, $this->getSubscriberHash($emailAddress), $body);
        } catch (\Exception $ex) {
            $this->setErrorData($ex->getTrace());
            $this->setMessages($ex->getCode() . ": " . $ex->getMessage());
            return false;
        }

        // Return
        return true;
    }

    /**
     * Generate subscriber-hash by email-address
     *
     * @param string $emailAddress
     * @return string
     */
    private function getSubscriberHash($emailAddress)
    {
        return md5(strtolower($emailAddress));
    }
}