<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: http://api.autotelexpro.nl/autotelexproapi.svc?singleWsdl
 */
namespace AtpCore\Api\Autotelex;

use AtpCore\Api\Autotelex\Response\Vehicle;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;
use AtpCore\Format;
use Laminas\Soap\Client;

class Api extends BaseClass
{

    private $client;
    private $debug;
    private $logger;
    private $originalResponse;
    private $password;
    private $sessionId;
    private $token;
    private $username;

    /**
     * Constructor
     *
     * @param string $wsdl
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($wsdl, $username, $password, $debug = false, \Closure $logger = null)
    {
        $this->client = new Client($wsdl, ['encoding' => 'UTF-8']);
        $this->client->setSoapVersion(SOAP_1_1);
        $this->sessionId = session_id();
        $this->username = $username;
        $this->password = $password;
        $this->debug = $debug;

        // Set custom logger
        $this->logger = $logger;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get (current) bid from Autotelex-request
     *
     * @param $externalId
     * @return int|bool
     */
    public function getBid($externalId)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Get vehicle-data
            $params = ["vendorToken"=>$token, "tp"=>["ExternalID"=>$externalId]];
            if ($this->debug) $this->log("request", "GetVehicle", json_encode($params));
            $result = $this->client->GetVehicle($params);
            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($result));
            $status = $result->GetVehicleResult->Status;

            if ($status->Code == 0) {
                if (isset($result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData->BiedingId)) {
                    $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                    if ($tmp->Soort == 16) return $tmp->Waarde;
                } else {
                    $tmp = $result->GetVehicleResult->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData;
                    if (is_array($tmp) && count($tmp) > 0) {
                        foreach ($tmp as $v) {
                            if ($v->Status == 2) continue;
                            if ($v->Soort == 16) return $v->Waarde;
                        }
                    }
                }
                return false;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Get (vehicle) data from Autotelex-PRO
     *
     * @param string $registration
     * @param string|null $atlCode
     * @param string|null $mileage
     * @return object|false
     */
    public function getData($registration, $atlCode = null, $mileage = null)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Set parameters
            $vehicleParams = ["kenteken" => $registration];
            if (!empty($atlCode)) $vehicleParams["AutotelexUitvoeringID"] = $atlCode;
            if (!empty($mileage)) $vehicleParams["kilometerstand"] = $mileage;
            $params = ["token"=>$token, "vehicle"=>$vehicleParams];

            if ($this->debug) $this->log("request", "GetVehicleDataPRO", json_encode($params));
            $result = $this->client->GetVehicleDataPRO($params);
            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "GetVehicleDataPRO", json_encode($result));
            $status = $result->GetVehicleDataPROResult->Status;

            // Check for valid status-code
            // Status-code: 0, OK
            // Status-code: 1, OK, with addition info (choose type-commercial)
            // Status-code: 2, General error
            // Status-code: 3, Customer-token invalid
            // Status-code: 4, Vendor-token invalid
            if (in_array($status->GenericCode, [0, 1]) || ($status->GenericCode == 2 && stristr($status->Message, "Ongeldig of onbekend kenteken"))) {
                return $result->GetVehicleDataPROResult;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Get vehicle-data
     *
     * @param int $externalId
     * @return Vehicle|object|bool
     */
    public function getVehicle($externalId, $output = null)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Get vehicle-data
            $params = ["vendorToken"=>$token, "tp"=>["ExternalID"=>$externalId]];
            if ($this->debug) $this->log("request", "GetVehicle", json_encode($params));
            $result = $this->client->GetVehicle($params);
            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($result));
            $status = $result->GetVehicleResult->Status;
            if ($status->Code == 0) {
                if ($output == "object") {
                    return $this->mapVehicleResponse($result->GetVehicleResult);
                } else {
                    return $result->GetVehicleResult;
                }
            } else {
                $this->setErrorData($status);
                $this->setMessages($status->Message);
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Send bid to Autotelex
     *
     * @param int $externalId
     * @param string $resultType
     * @param string $vatMarginType
     * @param int $bid
     * @param \DateTime $expirationDate
     * @param string $comment
     * @param int $rdwIdentificationNumber
     * @return bool|object
     */
    public function sendBid($externalId, $resultType, $vatMarginType, $bid, $expirationDate, $comment = null, $rdwIdentificationNumber = null)
    {
        if ($resultType == "not_interested") {
            return $this->sendNoInterest($externalId, $comment);
        }

        if ($bid > 0) {
            $btw = Format::lowercase($vatMarginType) == "btw";

            // Get token
            $token = $this->getToken();
            if ($token === false) return false;

            try {
                // Compose message/parameters
                $params = [
                    "vendorToken" => $token,
                    "ibp" => [
                        "ExternalID" => $externalId,
                        "SoortBod" => 16,
                        "Bod" => $bid,
                        "isBTWVoertuig" => $btw,
                        "Status" => 3,
                        "InclExclBTW" => "Incl. BTW",
                        "GeldigTot" => $expirationDate->format('c'),
                        "Naam" => "Autotaxatie (Autotaxatie)",
                        "Opmerking" => $comment
                    ],
                ];
                if (!empty($rdwIdentificationNumber)) {
                    $params["ibp"]["Buyer"] = [
                        "RdwNumber" => $rdwIdentificationNumber
                    ];
                }

                // Send bid
                if ($this->debug) $this->log("request", "InsertBod", json_encode($params));
                $result = $this->client->InsertBod($params);
                $this->setOriginalResponse($result);
                if ($this->debug) $this->log("response", "InsertBod", json_encode($result));
                $status = $result->InsertBodResult;
                if ($status->Code == 0) {
                    return true;
                } else {
                    return $status;
                }
            } catch (\Exception $e) {
                $this->setMessages($e->getMessage());
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Send no-interest to Autotelex
     *
     * @param int $externalId
     * @param string $comment
     * @return bool|object
     */
    public function sendNoInterest($externalId, $comment = null)
    {
        // Get token
        $token = $this->getToken();
        if ($token === false) return false;

        try {
            // Send no-interest
            $params = [
                "vendorToken" => $token,
                "vehicleId" => $externalId,
                "comment" => $comment
            ];
            if ($this->debug) $this->log("request", "NoInterest", json_encode($params));
            $result = $this->client->NoInterest($params);
            $this->setOriginalResponse($result);
            if ($this->debug) $this->log("response", "NoInterest", json_encode($result));
            $status = $result->NoInterestResult;
            if (property_exists($status, "Code") && $status->Code == 0) {
                return true;
            } else {
                return $status;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Get original-response
     *
     * @return mixed
     */
    public function getOriginalResponse()
    {
        return $this->originalResponse;
    }

    /**
     * Log message in default format
     *
     * @param string $type (request/response)
     * @param string $soapFunction
     * @param string $message
     * @return void
     */
    private function log($type, $soapFunction, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$soapFunction] $message";
        if (!empty($this->logger)) {
            $this->logger($message);
        } else {
            print("$message\n");
        }
    }

    /**
     * Get token
     *
     * @return string|false
     */
    private function getToken()
    {
        // Check if token already set
        if (!empty($this->token)) return $this->token;

        try {
            $params = ["username"=>$this->username, "password"=>$this->password];
            $result = $this->client->GetVendorToken($params);
            $status = $result->GetVendorTokenResult->Status;
            if ($status->Code == 0) {
                $this->token = $result->GetVendorTokenResult->Token;
                return $this->token;
            } else {
                $this->token = null;
                $this->setMessages($status->Message);
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }
    }

    /**
     * Log message via custom log-function
     *
     * @param string $message
     * @return void
     */
    private function logger($message)
    {
        $logger = $this->logger;
        return $logger($message);
    }

    /**
     * Map response to (internal) Vehicle-object
     *
     * @param object $response
     * @param bool $failOnUndefinedProperty
     * @return Vehicle|false
     */
    private function mapVehicleResponse($response, $failOnUndefinedProperty = true)
    {
        $response = $this->fixDataTypes($response);

        try {
            // Setup JsonMapper
            $responseClass = new Vehicle();
            $mapper = new JsonMapperExtension();
            $mapper->bExceptionOnUndefinedProperty = $failOnUndefinedProperty;
            $mapper->bStrictObjectTypeChecking = true;
            $mapper->bExceptionOnMissingData = true;
            $mapper->bStrictNullTypes = true;
            $mapper->bCastToExpectedType = false;

            // Map response to internal object
            $object = $mapper->map($response, $responseClass);
            $valid = $mapper->isValid($object, get_class($responseClass));
            if ($valid === false) {
                $this->setMessages($mapper->getMessages());
                return false;
            }
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            if (stristr($e->getMessage(), "JSON property") && stristr($e->getMessage(), "does not exist in object of type AtpCore\Api\Autotelex\Response")) {
                return $this->mapVehicleResponse($response, false);
            }
            return false;
        }

        // Return
        return $object;
    }

    private function fixDataTypes($response)
    {
        // Fix inconsistent types (array/single object -> always array)
        if (is_object($response->OptionalTypes->VehicleType)) {
            $response->OptionalTypes->VehicleType = [$response->OptionalTypes->VehicleType];
        }
        if (property_exists($response->VehicleInfo, "Accessoires") && is_object($response->VehicleInfo->Accessoires->Options)) {
            $response->VehicleInfo->Accessoires->Options = [$response->VehicleInfo->Accessoires->Options];
        }
        if (!empty($response->VehicleInfo->Opties->Options)) {
            if (is_object($response->VehicleInfo->Opties->Options)) {
                $response->VehicleInfo->Opties->Options = [$response->VehicleInfo->Opties->Options];
            }
            foreach ($response->VehicleInfo->Opties->Options as $key => $option) {
                if (is_object($option->ManufacturerOptionCodes) && property_exists($option->ManufacturerOptionCodes, "ManufacturerOption") && is_object($option->ManufacturerOptionCodes->ManufacturerOption)) {
                    $response->VehicleInfo->Opties->Options[$key]->ManufacturerOptionCodes->ManufacturerOption = [$option->ManufacturerOptionCodes->ManufacturerOption];
                }
            }
        }
        if (!empty($response->VehicleInfo->Pakketten->Packets)) {
            if (is_object($response->VehicleInfo->Pakketten->Packets)) {
                $response->VehicleInfo->Pakketten->Packets = [$response->VehicleInfo->Pakketten->Packets];
            }
            foreach ($response->VehicleInfo->Pakketten->Packets as $key => $package) {
                if (is_object($package->Opties) && is_object($package->Opties->Options)) {
                    $response->VehicleInfo->Pakketten->Packets[$key]->Opties->Options = [$package->Opties->Options];
                }
                if (!empty($response->VehicleInfo->Pakketten->Packets[$key]->Opties)) {
                    foreach ($response->VehicleInfo->Pakketten->Packets[$key]->Opties->Options as $k => $option) {
                        if (is_object($option->ManufacturerOptionCodes) && property_exists($option->ManufacturerOptionCodes, "ManufacturerOption") && is_object($option->ManufacturerOptionCodes->ManufacturerOption)) {
                            $response->VehicleInfo->Pakketten->Packets[$key]->Opties->Options[$k]->ManufacturerOptionCodes->ManufacturerOption = [$option->ManufacturerOptionCodes->ManufacturerOption];
                        }
                    }
                }
            }
        }
        if (is_object($response->VehicleInfo->RDWVoertuigData->VoertuigData)) {
            $response->VehicleInfo->RDWVoertuigData->VoertuigData = [$response->VehicleInfo->RDWVoertuigData->VoertuigData];
        }
        if (!empty($response->VehicleInfo->StandaardOpties->Options)) {
            if (is_object($response->VehicleInfo->StandaardOpties->Options)) {
                $response->VehicleInfo->StandaardOpties->Options = [$response->VehicleInfo->StandaardOpties->Options];
            }
            foreach ($response->VehicleInfo->StandaardOpties->Options as $key => $option) {
                if (is_object($option->ManufacturerOptionCodes) && property_exists($option->ManufacturerOptionCodes, "ManufacturerOption") && is_object($option->ManufacturerOptionCodes->ManufacturerOption)) {
                    $response->VehicleInfo->StandaardOpties->Options[$key]->ManufacturerOptionCodes->ManufacturerOption = [$option->ManufacturerOptionCodes->ManufacturerOption];
                }
            }
        }
        if (is_object($response->VehicleInfo2->Restwaarden->RestWaarden)) {
            $response->VehicleInfo2->Restwaarden->RestWaarden = [$response->VehicleInfo2->Restwaarden->RestWaarden];
        }
        if (property_exists($response->VehicleInfo2->VoertuigVariabelen->Accessoires, "Options") && is_object($response->VehicleInfo2->VoertuigVariabelen->Accessoires->Options)) {
            $response->VehicleInfo2->VoertuigVariabelen->Accessoires->Options = [$response->VehicleInfo2->VoertuigVariabelen->Accessoires->Options];
        }
        if (property_exists($response->VehicleInfo2->VoertuigVariabelen->BandenGegevens, "BandenParameters") && is_object($response->VehicleInfo2->VoertuigVariabelen->BandenGegevens->BandenParameters)) {
            $response->VehicleInfo2->VoertuigVariabelen->BandenGegevens->BandenParameters = [$response->VehicleInfo2->VoertuigVariabelen->BandenGegevens->BandenParameters];
        }
        if (is_object($response->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData->TMStatusHistorieLijst->TMStatusHistorie)) {
            $response->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData->TMStatusHistorieLijst->TMStatusHistorie = [$response->VehicleInfo2->VoertuigVariabelen->Biedingen->BiedingData->TMStatusHistorieLijst->TMStatusHistorie];
        }
        if (property_exists($response->VehicleInfo2->VoertuigVariabelen, "ChargingCableTypes") && is_object($response->VehicleInfo2->VoertuigVariabelen->ChargingCableTypes->ChargingCableModel)) {
            $response->VehicleInfo2->VoertuigVariabelen->ChargingCableTypes->ChargingCableModel = [$response->VehicleInfo2->VoertuigVariabelen->ChargingCableTypes->ChargingCableModel];
        }
        if (property_exists($response->VehicleInfo2->VoertuigVariabelen, "Files") && is_object($response->VehicleInfo2->VoertuigVariabelen->Files->UploadFileParameters)) {
            $response->VehicleInfo2->VoertuigVariabelen->Files->UploadFileParameters = [$response->VehicleInfo2->VoertuigVariabelen->Files->UploadFileParameters];
        }
        if (!empty($response->VehicleInfo2->VoertuigVariabelen->Opties->Options)) {
            if (is_object($response->VehicleInfo2->VoertuigVariabelen->Opties->Options)) {
                $response->VehicleInfo2->VoertuigVariabelen->Opties->Options = [$response->VehicleInfo2->VoertuigVariabelen->Opties->Options];
            }
            foreach ($response->VehicleInfo2->VoertuigVariabelen->Opties->Options as $key => $option) {
                if (is_object($option->ManufacturerOptionCodes) && property_exists($option->ManufacturerOptionCodes, "ManufacturerOption") && is_object($option->ManufacturerOptionCodes->ManufacturerOption)) {
                    $response->VehicleInfo2->VoertuigVariabelen->Opties->Options[$key]->ManufacturerOptionCodes->ManufacturerOption = [$option->ManufacturerOptionCodes->ManufacturerOption];
                }
            }
        }
        if (!empty($response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets)) {
            if (is_object($response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets)) {
                $response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets = [$response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets];
            }
            foreach ($response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets as $key => $package) {
                if (is_object($package->Opties->Options)) {
                    $response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets[$key]->Opties->Options = [$package->Opties->Options];
                }
                if (!empty($response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets[$key]->Opties)) {
                    foreach ($response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets[$key]->Opties->Options as $k => $option) {
                        if (is_object($option->ManufacturerOptionCodes) && property_exists($option->ManufacturerOptionCodes, "ManufacturerOption") && is_object($option->ManufacturerOptionCodes->ManufacturerOption)) {
                            $response->VehicleInfo2->VoertuigVariabelen->Pakketten->Packets[$key]->Opties->Options[$k]->ManufacturerOptionCodes->ManufacturerOption = [$option->ManufacturerOptionCodes->ManufacturerOption];
                        }
                    }
                }
            }
        }
        if (is_object($response->VehicleInfo2->VoertuigVariabelen->ReportURLs->ExternalURL)) {
            $response->VehicleInfo2->VoertuigVariabelen->ReportURLs->ExternalURL = [$response->VehicleInfo2->VoertuigVariabelen->ReportURLs->ExternalURL];
        }
        if (property_exists($response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades, "SchadeOmschrijving") && is_object($response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades->SchadeOmschrijving)) {
            $response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades->SchadeOmschrijving = [$response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades->SchadeOmschrijving];
        }
        if (!empty($response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades->SchadeOmschrijving)) {
            foreach ($response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades->SchadeOmschrijving AS $key => $damage) {
                if (property_exists($damage->SchadeFotoIds, "int") && is_int($damage->SchadeFotoIds->int)) {
                    $response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades->SchadeOmschrijving[$key]->SchadeFotoIds->int = [$damage->SchadeFotoIds->int];
                }
                if (property_exists($damage->SchadefotoURLs, "string") && is_string($damage->SchadefotoURLs->string)) {
                    $response->VehicleInfo2->VoertuigVariabelen->SchadeGegevens->Schades->SchadeOmschrijving[$key]->SchadefotoURLs->string = [$damage->SchadefotoURLs->string];
                }
            }
        }
        if (!empty($response->VehicleInfo2->VoertuigVariabelen->StandaardOpties->Options)) {
            if (is_object($response->VehicleInfo2->VoertuigVariabelen->StandaardOpties->Options)) {
                $response->VehicleInfo2->VoertuigVariabelen->StandaardOpties->Options = [$response->VehicleInfo2->VoertuigVariabelen->StandaardOpties->Options];
            }
            foreach ($response->VehicleInfo2->VoertuigVariabelen->StandaardOpties->Options as $key => $option) {
                if (is_object($option->ManufacturerOptionCodes) && property_exists($option->ManufacturerOptionCodes, "ManufacturerOption") && is_object($option->ManufacturerOptionCodes->ManufacturerOption)) {
                    $response->VehicleInfo2->VoertuigVariabelen->StandaardOpties->Options[$key]->ManufacturerOptionCodes->ManufacturerOption = [$option->ManufacturerOptionCodes->ManufacturerOption];
                }
            }
        }

        // Return
        return $response;
    }

    /**
     * Set original-response
     *
     * @param $originalResponse
     */
    private function setOriginalResponse($originalResponse)
    {
        $this->originalResponse = $originalResponse;
    }
}