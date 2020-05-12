<?php
/**
 * API-information: https://autotaxatiepartners.atlassian.net/wiki/download/attachments/1409033/OrderExchangeManual.pdf
 */
namespace AtpCore\Api\RGB;

use AtpCore\BaseClass;
use GuzzleHttp\Client;
use SimpleXMLElement;

class Api extends BaseClass
{

    private $client;
    private $clientHeaders;
    private $xml;

    /**
     * Constructor
     *
     * @param string $hostname
     * @param string $apiKey
     * @param string $recipientClientId
     * @param boolean $debug
     */
    public function __construct($hostname, $apiKey, $recipientClientId, $debug = false)
    {
        // Set client
        $this->client = new Client(['base_uri'=>$hostname, 'http_errors'=>false, 'debug'=>$debug]);

        // Reset error-messages
        $this->resetErrors();

        // Set default header for client-requests
        $this->clientHeaders = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/xml',
            'Content-Type' => 'application/xml',
            "tp-oe-Recipient" => $recipientClientId,
        ];
    }

    /**
     * Create transport-request
     *
     * @return object|SimpleXMLElement
     */
    public function createTransport()
    {
        if (!isset($this->xml) || empty($this->xml)) {
            $this->setMessages("No transport-request (data) available");
            return false;
        }

        // Convert XML into string
        $body = $this->xml->asXML();

        // Execute call
        $requestHeader = $this->clientHeaders;
        $requestHeader['Content-Length'] = strlen($body);
        $result = $this->client->post('order', ['headers'=>$requestHeader, 'body'=>$body]);
        if ($result->getStatusCode() !== 202) {
            $this->setMessages("{$result->getStatusCode()}: {$result->getReasonPhrase()}");
            return false;
        }

        // Convert result into response
        if ($requestHeader['Accept'] == 'application/json') {
            $response = json_decode($result->getBody()->getContents());
        } else {
            $response = new SimpleXMLElement($result->getBody()->getContents());
        }

        // Return
        return $response;
    }

    public function setAddress($type, $companyName, $personName, $street, $number, $zipcode, $city, $country, $email, $phone, $comment)
    {
        // Add data to XML
        $address = $this->xml->addChild($type);
        $address->addChild('remarks', html_entity_decode($comment));
        $addressLocation = $address->addChild('location');
        $addressLocationContactDetails = $addressLocation->addChild('contact-details');
        $addressLocationContactDetails->addChild('contact', html_entity_decode($personName));
        $addressLocationContactDetails->addChild('email-address', $email);
        $addressLocationContactDetails->addChild('phone-number', $phone);
        $addressLocationCoordinates = $addressLocation->addChild('coordinates');
        $addressLocationCoordinates->addChild('longitude', 0);
        $addressLocationCoordinates->addChild('latitude', 0);
        $addressLocation->addChild('country', 'NLD');
        $addressLocation->addChild('city', html_entity_decode($city));
        $addressLocation->addChild('postal-code', $zipcode);
        $addressLocation->addChild('house-number', trim($number));
        $addressLocation->addChild('street', html_entity_decode($street));
        $addressLocation->addChild('name', html_entity_decode($companyName));
        $addressLocation->addChild('remarks', html_entity_decode($comment));
    }

    public function setGeneral($orderNumber, $reference, $dateDeadline)
    {
        // Initialize XML
        $this->xml = new SimpleXMLElement('<order xmlns="http://schema.transplan.nl/v1/order-exchange"/>');

        // Add data to XML
        $this->xml->addChild('order-id', $orderNumber);
        $this->xml->addChild('reference', $reference);
        $transportDate = $this->xml->addChild('transport-date');
        $transportDate->addChild('unload-date', $dateDeadline->format('Y-m-d'));
        $transportDate->addChild('load-date', (new \DateTime())->format('Y-m-d'));
    }

    public function setVehicle($vin, $registration, $vehicleType, $make, $model, $color, $comment)
    {
        // Add data to XML
        $shipment = $this->xml->addChild('shipment');
        //$shipmentDimensions = $shipment->addChild('dimensions');
        //$shipmentDimensions->addChild('length', '');
        //$shipmentDimensions->addChild('height', '');
        //$shipmentDimensions->addChild('width', '');
        //$shipment->addChild('weight', '');
        $shipment->addChild('drivable', true);
        $shipment->addChild('rollable', true);
        //$shipment->addChild('first-registration', '');
        $shipment->addChild('vin', $vin);
        $shipment->addChild('vehicle-registration-plate', $registration);
        $shipment->addChild('type', $vehicleType);
        $shipment->addChild('make', html_entity_decode(trim(preg_replace('/ {2,}/', ' ', $make))));
        $shipment->addChild('model', html_entity_decode(trim(preg_replace('/ {2,}/', ' ', $model))));
        $shipment->addChild('colour', html_entity_decode($color));
        $shipment->addChild('remarks', html_entity_decode($comment));
    }

}