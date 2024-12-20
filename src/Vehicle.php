<?php

namespace AtpCore;

class Vehicle
{
    /**
     * Convert power (kW) into horsepower
     *
     * @param integer $power
     * @return string
     */
    public static function convertPower($power, $sourceUnit = "kW")
    {
        if (!empty($power)) {
            $factor = 1.359623;
            if (Format::lowercase($sourceUnit) == "pk") return round(((float) $power) / $factor);
            else return round(((float) $power) * $factor);
        } else {
            return 0;
        }
    }

    /**
     * Validate Vehicle Identification Number (VIN) format
     *
     * @param string $vin
     * @param integer $year
     * @return boolean
     */
    public static function isValidVin($vin, $year)
    {
        // Specific check for vehicles older than 1981
        if ($year < 1981) {
            return preg_match('/^[A-HJ-NPR-Z0-9]{11,16}$/', $vin);
        }

        // Return
        return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin);
    }
}