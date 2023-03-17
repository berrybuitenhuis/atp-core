<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * API-information: https://docs.carcollect.com/
 */
namespace AtpCore\Api\CarCollect;

use AtpCore\Api\CarCollect\Response\Vehicle;
use AtpCore\BaseClass;
use AtpCore\Extension\JsonMapperExtension;
use GraphQL\Client;
use GraphQL\Mutation;
use GraphQL\Query;

class Api extends BaseClass
{

    private $debug;
    private $host;
    private $logger;
    private $originalResponse;
    private $password;
    private $sessionId;
    private $token;
    private $username;

    /**
     * Constructor
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param boolean $debug
     * @param \Closure|null $logger
     */
    public function __construct($host, $username, $password, $debug = false, \Closure $logger = null)
    {
        $this->host = $host;
        $this->debug = $debug;
        $this->password = $password;
        $this->sessionId = session_id();
        $this->username = $username;

        // Set custom logger
        $this->logger = $logger;

        // Reset error-messages
        $this->resetErrors();
    }

    /**
     * Get vehicle-data
     *
     * @param int $externalId
     * @return Vehicle|object|bool
     */
    public function getVehicle($externalId)
    {
        // Get token
        $token = $this->getToken();
        var_dump($token);
        if ($token === false) return false;

        try {
            // Get vehicle-data
            $query = (new Query('getTradeDossier'))
                ->setArguments(['id'=>$externalId])
                ->setSelectionSet(
                    ['id','brand','model','version','license_plate','award_amount','award_type','book_value','build_year','destination',
                        'fuel','mileage_exact','mileage_expected','mileage','nap_check','power','sorting_date','status','tagline','trade_value_average',
                        'transmission','vat_vehicle','vehicle_type','vin_number','supply_countries','demand_countries','addition_rate_valid_until','addition_rate',
                        'co2_emission','energy_label','intake_date','intake_date_expected','number_of_keys','rdw_euro_class','rdw_max_mass_restrained',
                        'rdw_max_mass_unrestrained','rdw_max_mass','rdw_payload','steering_wheel_side',
                        (new Query('exterior'))->setSelectionSet(['alloy_wheels','exterior_damage_free','exterior_notes','metallic_paint','panoramic_roof','parking_sensor','rear_view_camera','sliding_roof','towbar','xenon']),
                        (new Query('interior'))->setSelectionSet(['adaptive_cruise_control','airco','charging_cable_present','climate_control','cruise_control','interior_damage_free','interior_notes','jack_present','leather_furnishing','navigation','rear_shelf_present','spare_wheel_present','tire_repair_kit_present']),
                        (new Query('other'))->setSelectionSet(['apk_valid_until','body_work','color','cylinders','doors','drive','empty_weight','engine_capacity','external_notes','factory_options','first_registration','import_other_continent','import_vehicle','instruction_manual_present','internal_notes','main_key_present','maintenance_book_present','new_price','refund_on_export','spare_key_present','tax_gross','tax_rest','taxi','trade_value_average_retail']),
                        (new Query('technical'))->setSelectionSet(['maintenance_last','technical_damage_free','technical_notes','timing_belt_replaced']),
                        (new Query('wheels'))->setSelectionSet(['profile_depth_left_front','profile_depth_left_rear','profile_depth_right_front','profile_depth_right_rear','rim_inches','tire_brand','tire_height','tire_type','tire_width','wheels_damage_free','wheels_notes']),
                        (new Query('damages'))->setSelectionSet(['id','description','location','recovery_costs','solution','type','visible_for_trader',
                            (new Query('images'))->setSelectionSet(['id','position','url_small','url_big','type','label'])
                        ]),
                        (new Query('documents'))->setSelectionSet(['id','label','url','type','name']),
                        (new Query('images'))->setSelectionSet(['id','position','url_small','url_big','type','label'])
                    ]
                );

            if ($this->debug) $this->log("request", "GetVehicle", json_encode($query));
            $response = $this->getClient($token)->runQuery($query);
            $this->setOriginalResponse($response->getData());
            if ($this->debug) $this->log("response", "GetVehicle", json_encode($response->getData()));
            return $this->mapVehicleResponse($response->getData()->getTradeDossier);
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
     * @param string $method
     * @param string $message
     * @return void
     */
    private function log($type, $method, $message)
    {
        $date = (new \DateTime())->format("Y-m-d H:i:s");
        $message = "[$date][$this->sessionId][$type][$method] $message";
        if (!empty($this->logger)) {
            $this->logger($message);
        } else {
            print("$message\n");
        }
    }

    /**
     * Initialize GraphQl-client
     *
     * @param string|null $token
     * @return Client
     */
    private function getClient($token = null)
    {
        if (!empty($token)) {
            $client = new Client($this->host, ["Authorization" => "Bearer $token"]);
        } else {
            $client = new Client($this->host);
        }

        // Return
        return $client;
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
            // Get token
            $mutation = (new Mutation('loginApi'))
                ->setArguments(['email'=>$this->username, 'password'=>$this->password])
                ->setSelectionSet(['id', 'email', 'access_token']);

            $response = $this->getClient()->runQuery($mutation);
            $result = $response->getData();

            // Set token
            $this->token = $result->loginApi->access_token;

            // Return
            return $this->token;
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
     * @return Vehicle|false
     */
    private function mapVehicleResponse($response)
    {
        try {
            // Setup JsonMapper
            $responseClass = new Vehicle();
            $mapper = new JsonMapperExtension();
            $mapper->bExceptionOnUndefinedProperty = true;
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
            return false;
        }

        // Return
        return $object;
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