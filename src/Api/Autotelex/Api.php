<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: http://api.autotelexpro.nl/autotelexproapi.svc?singleWsdl
 */
namespace AtpCore\Api\Autotelex;

use AtpCore\BaseClass;
use Zend\Soap\Client;

class Api extends BaseClass
{

    private $client;
    private $debug;
    private $token;

    /**
     * Constructor
     *
     * @param string $wsdl
     * @param string $username
     * @param string $password
     * @param boolean $debug
     */
    public function __construct($wsdl, $username, $password, $debug = false)
    {
        $this->client = new Client($wsdl, ['encoding' => 'UTF-8']);
        $this->client->setSoapVersion(SOAP_1_1);
        $this->debug = $debug;

        // Reset error-messages
        $this->resetErrors();

        // Get token
        $this->token = $this->getToken($username, $password);
    }

    /**
     * Get (current) bid from Autotelex-request
     *
     * @param $externalId
     * @return int|bool
     */
    public function getBid($externalId)
    {
        $params = ["vendorToken"=>$this->token, "tp"=>["ExternalID"=>$externalId]];
        $result = $this->client->GetVehicle($params);
        $status = $result->GetVehicleResult->Status;

        if ($status->Code == 0) {
            if (isset($result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData->BiedingId)) {
                $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                if ($tmp->Soort == 16) return $tmp->Waarde;
            } else {
                $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                if (is_array($tmp) && count($tmp) > 0) {
                    foreach ($tmp AS $k => $v) {
                        if ($v->Status == 2) continue;
                        if ($v->Soort == 16) return $v->Waarde;
                    }
                }
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * Get token
     *
     * @param string $username
     * @param string $password
     * @return string
     */
    private function getToken($username, $password)
    {
        $params = ["username"=>$username, "password"=>$password];

        $result = $this->client->GetVendorToken($params);
        $status = $result->GetVendorTokenResult->Status;
        if ($status->Code == 0) {
            $token = $result->GetVendorTokenResult->Token;
            return $token;
        } else {
            return null;
        }
    }

    /**
     * Send bid to Autotelex
     *
     * @param int $externalId
     * @param string $statusType
     * @param string $vatMarginType
     * @param int $bid
     * @param \DateTime $expirationDate
     * @return bool|object
     */
    public function sendBid($externalId, $statusType, $vatMarginType, $bid, $expirationDate)
    {
        if ($statusType == "not interested") {
            return $this->sendNoInterest($externalId);
        }

        if ($bid > 0) {
            $btw = (strtolower($vatMarginType) == "btw") ? true : false;

            // Send bid
            $params = [
                "vendorToken" => $this->token,
                "ibp" => [
                    "ExternalID" => $externalId,
                    "SoortBod" => 16,
                    "Bod" => $bid,
                    "isBTWVoertuig" => $btw,
                    "Status" => 3,
                    "InclExclBTW" => "Incl. BTW",
                    "GeldigTot" => $expirationDate->format('c'),
                    "Naam" => "Autotaxatie (Autotaxatie)"
                ]
            ];
            $result = $this->client->InsertBod($params);

            $status = $result->InsertBodResult;
            if ($status->Code == 0) {
                return true;
            } else {
                return $status;
            }
        } else {
            return false;
        }
    }

    /**
     * Send no-interest to Autotelex
     *
     * @param int $externalId
     * @return bool|object
     */
    public function sendNoInterest($externalId)
    {
        // Send no-interest
        $params = ["vendorToken"=>$this->token, "vehicleId"=>$externalId];
        $result = $this->client->NoInterest($params);

        $status = $result->NoInterestResult;
        if ($status->Code == 0) {
            return true;
        } else {
            return $status;
        }
    }
}