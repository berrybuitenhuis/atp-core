<?php

namespace AtpCore;

class Format
{
    /**
     * @param string $countryCode
     * @param string $nationalCode
     * @param string $subscriberNumber
     * @return null|string
     */
    public static function phoneNumber($countryCode, $nationalCode, $subscriberNumber)
    {
        // Skip if no phone-number available
        if (empty($subscriberNumber)) return null;

        // Format phone-number
        $phoneNumber = '';

        // Add country-code to phone-number
        if (is_numeric(str_replace('+', '', $countryCode))) {
            $phoneNumber .= "+";
        }
        $phoneNumber .= trim(str_replace("00", "", str_replace('+', '', $countryCode)));

        // Add national-code to phone-number
        if (strlen($nationalCode) != 0 && is_numeric($nationalCode)) {
            if (strlen($phoneNumber) > 0 && substr($nationalCode, 0, 1) == '0') {
                $phoneNumber .= " (0)" . substr($nationalCode, 1, strlen($nationalCode) - 1);
            } elseif (strlen($phoneNumber) == 0) {
                $phoneNumber .= $nationalCode;
            } else {
                $phoneNumber .= " (0)" . $nationalCode;
            }
        }

        // Add subscriber-number to phone-number
        if (!empty(preg_replace("/\D/", "", $subscriberNumber)))
            $phoneNumber .= '-' . $subscriberNumber;

        // Return
        return $phoneNumber;
    }

}