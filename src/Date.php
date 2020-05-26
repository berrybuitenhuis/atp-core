<?php

namespace AtpCore;

use DateInterval;
use DateTime;
use Exception;
use Throwable;

class Date extends BaseClass
{

    private $date;

    /**
     * @param DateTime $date
     */
    public function __construct($date = null)
    {
        // Set date
        $this->date = (empty($date)) ? new DateTime() : $date;
    }

    /**
     * Return format of date-string
     *
     * @param string $dateString
     * @return string|null
     */
    public static function getDateFormat($dateString) {
        // Initialize date-format
        $dateFormat = null;

        // Check format of date-string
        if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dateString)) {
            $dateFormat = "Y-m-d";
        } elseif (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/", $dateString)) {
            $dateFormat = "d-m-Y";
        }

        // Return
        return $dateFormat;
    }

    /**
     * Add interval (minutes, hours, days) to date-time
     *
     * @param int $interval
     * @param string $format
     * @param array|bool $weekendDays
     * @return bool|DateTime
     */
    public function addInterval($interval, $format = "seconds", $weekendDays = null)
    {
        // Convert interval
        $res = $this->convertInterval($interval, $format);
        if ($res !== false) {
            $numberOfDays = $res['numberOfDays'];
            $intervalSeconds = $res['intervalSeconds'];
        } else {
            return false;
        }

        // Add (number of) days to date
        try {
            $result = $this->addWorkDays($numberOfDays, $weekendDays);
            if ($result === false) return false;
        } catch (Throwable $e) {
            $this->addMessage("Function addWorkDays failed");
            $this->setErrorData($e);
            return false;
        }


        // Add remainder seconds
        if ($intervalSeconds > 0) {
            try {
                $interval = new DateInterval("PT" . $intervalSeconds . "S");
                $this->date->add($interval);
            } catch (Throwable $e) {
                $this->addMessage("Invalid format provided (PT{$intervalSeconds}S)");
                $this->setErrorData($e);
                return false;
            }
        }

        // Return (new) date
        return $this->date;
    }

    /**
     * Subtract interval (minutes, hours, days) from date-time
     *
     * @param int $interval
     * @param string $format
     * @param array|bool $weekendDays
     * @return bool|DateTime
     */
    public function subtractInterval($interval, $format = "seconds", $weekendDays = null)
    {
        // Convert interval
        $res = $this->convertInterval($interval, $format);
        if ($res !== false) {
            $numberOfDays = $res['numberOfDays'];
            $intervalSeconds = $res['intervalSeconds'];
        } else {
            return false;
        }

        // Subtract (number of) days from date
        try {
            $result = $this->subtractWorkDays($numberOfDays, $weekendDays);
            if ($result === false) return false;
        } catch (Throwable $e) {
            $this->addMessage("Function addWorkDays failed");
            $this->setErrorData($e);
            return false;
        }


        // Subtract remainder seconds
        if ($intervalSeconds > 0) {
            try {
                $interval = new DateInterval("PT" . $intervalSeconds . "S");
                $this->date->sub($interval);
            } catch (Throwable $e) {
                $this->addMessage("Invalid format provided (PT{$intervalSeconds}S)");
                $this->setErrorData($e);
                return false;
            }
        }

        // Return (new) date
        return $this->date;
    }

    /**
     * Add number of workdays to date
     *
     * @param int $numberOfDays
     * @param array|bool $weekendDays
     * @return boolean
     */
    private function addWorkDays($numberOfDays, $weekendDays = null)
    {
        for ($i = 1; $i <= $numberOfDays; $i++) {
            try {
                $this->date->add(new DateInterval("P1D"));
            } catch (Throwable $e) {
                $this->addMessage("Invalid format provided");
                $this->setErrorData($e);
                return false;
            }

            if ($weekendDays !== false) {
                try {
                    while ($this->isDayOff($weekendDays)) {
                        try {
                            $this->date->add(new DateInterval("P1D"));
                        } catch (Throwable $e) {
                            $this->addMessage("Invalid format provided");
                            $this->setErrorData($e);
                            return false;
                        }
                    }
                } catch (Throwable $e) {
                    $this->addMessage("Function isDayOff failed");
                    $this->setErrorData($e);
                    return false;
                }
            }
        }

        // Return
        return true;
    }

    /**
     * Subtract number of workdays to date
     *
     * @param int $numberOfDays
     * @param array|bool $weekendDays
     * @return boolean
     */
    private function subtractWorkDays($numberOfDays, $weekendDays = null)
    {
        for ($i = 1; $i <= $numberOfDays; $i++) {
            try {
                $this->date->sub(new DateInterval("P1D"));
            } catch (Throwable $e) {
                $this->addMessage("Invalid format provided");
                $this->setErrorData($e);
                return false;
            }

            if ($weekendDays !== false) {
                try {
                    while ($this->isDayOff($weekendDays)) {
                        try {
                            $this->date->sub(new DateInterval("P1D"));
                        } catch (Throwable $e) {
                            $this->addMessage("Invalid format provided");
                            $this->setErrorData($e);
                            return false;
                        }
                    }
                } catch (Throwable $e) {
                    $this->addMessage("Function isDayOff failed");
                    $this->setErrorData($e);
                    return false;
                }
            }
        }

        // Return
        return true;
    }

    private function convertInterval($interval, $format) {
        // Set interval (in seconds) by format
        switch (strtolower($format)) {
            case "second":
            case "seconds":
            case "seconden":
            case "sec":
                $numberOfDays = floor($interval / (60*60*24));
                $intervalSeconds = $interval - ($numberOfDays * 60*60*24);
                break;
            case "minute":
            case "minutes":
            case "minuten":
            case "min":
                $numberOfDays = floor($interval / (60*24));
                $intervalSeconds = ($interval - ($numberOfDays * 60*24)) * 60;
                break;
            case "hour":
            case "hours":
            case "uren":
            case "u":
                $numberOfDays = floor($interval / 24);
                $intervalSeconds = ($interval - ($numberOfDays * 24)) * (60*60);
                break;
            case "day":
            case "days":
            case "dagen":
            case "d":
                $numberOfDays = $interval;
                $intervalSeconds = 0;
                break;
            default:
                $this->addMessage("Invalid format provided ({$format})");
                return false;
        }

        return ["numberOfDays"=>$numberOfDays, "intervalSeconds"=>$intervalSeconds];
    }

    /**
     * Check if date is day-off
     *
     * @param array $weekendDays
     * @return boolean
     * @throws Exception
     */
    private function isDayOff ($weekendDays = null)
    {
        if (!is_array($weekendDays)) $weekendDays = ["sun"];

        if (in_array(strtolower($this->date->format("D")), $weekendDays)) {
            return true;
        } else {
            // Check Easter (Pasen)
            $easter = new DateTime();
            $easter->setTimestamp(easter_date($this->date->format("Y")));
            if ($this->date->format("Y-m-d") == $easter->format("Y-m-d")) return true;
            $easterMonday = clone $easter;
            $easterMonday->add(new DateInterval('P1D'));
            if ($this->date->format("Y-m-d") == $easterMonday->format("Y-m-d")) return true;

            // Check Ascension Day (Hemelvaartsdag)
            $ascensionDay = clone $easter;
            $ascensionDay->add(new DateInterval('P39D'));
            if ($this->date->format("Y-m-d") == $ascensionDay->format("Y-m-d")) return true;

            // Check Pentecost (Pinksteren)
            $pentecost = clone $ascensionDay;
            $pentecost->add(new DateInterval('P10D'));
            if ($this->date->format("Y-m-d") == $pentecost->format("Y-m-d")) return true;
            $pentecostMonday = clone $pentecost;
            $pentecostMonday->add(new DateInterval('P1D'));
            if ($this->date->format("Y-m-d") == $pentecostMonday->format("Y-m-d")) return true;

            // Check Kingsday (Koningsdag)
            $kingsDay = new DateTime($this->date->format("Y") . "-04-27");
            if ($kingsDay->format('D') === 'Sun') {
                $kingsDay->sub(new DateInterval('P1D'));
            }
            if ($this->date->format("Y-m-d") == $kingsDay->format("Y-m-d")) return true;

            // Check Liberation Day (Bevrijdingsdag)
            if (($this->date->format("Y") % 5) == 0) {
                $liberationDay = new DateTime($this->date->format("Y") . "-05-05");
                if ($this->date->format("Y-m-d") == $liberationDay->format("Y-m-d")) return true;
            }

            // Check Christmas Days (Kerstmis)
            $christmasDay = new DateTime($this->date->format("Y") . "-12-25");
            if ($this->date->format("Y-m-d") == $christmasDay->format("Y-m-d")) return true;
            $christmasDaySecond = new DateTime($this->date->format("Y") . "-12-26");
            if ($this->date->format("Y-m-d") == $christmasDaySecond->format("Y-m-d")) return true;

            // Check New Years Day(Nieuwjaarsdag)
            $newYearsDay = new DateTime($this->date->format("Y") . "-01-01");
            if ($this->date->format("Y-m-d") == $newYearsDay->format("Y-m-d")) return true;
        }

        // Return
        return false;
    }

}