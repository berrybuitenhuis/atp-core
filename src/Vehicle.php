<?php

namespace AtpCore;

class Vehicle
{
    /**
     * Convert power (kW) into horse-power
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
}