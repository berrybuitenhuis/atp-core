<?php

namespace AtpCore\Api\PDOK\Response;

class AddressResult
{
    /** @var AddressResponse */
    public $response;

    /**
     * Get latitude of the first address-document
     *
     * @return float|null
     */
    public function getLatitude()
    {
        return $this->getLatLong()["latitude"] ?? null;
    }

    /**
     * Get longitude of the first address-document
     *
     * @return float|null
     */
    public function getLongitude()
    {
        return $this->getLatLong()["longitude"] ?? null;
    }

    /**
     * Parse latitude/longitude from first address-document
     *
     * @return array|null
     */
    private function getLatLong()
    {
        $document = $this->response->docs[0] ?? null;
        if (empty($document) || empty($document->centroide_ll)) {
            return null;
        }

        // Format is POINT(longitude latitude)
        if (!preg_match('/POINT\s*\(\s*(-?\d+(?:\.\d+)?)\s+(-?\d+(?:\.\d+)?)\s*\)/i', $document->centroide_ll, $matches)) {
            return null;
        }

        return [
            "latitude" => (float) $matches[2],
            "longitude" => (float) $matches[1],
        ];
    }
}