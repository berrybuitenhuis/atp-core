<?php

namespace AtpCore;

class Date
{

    /**
     * Add interval (minutes, hours, days) to date-time
     *
     * @param \DateTime $date
     * @param int $interval
     * @param string $format
     * @param array $weekendDays
     * @return false|string
     */
    public function addInterval(\DateTime $date, $interval, $format = "seconds", $weekendDays = null)
    {
        // Set interval (in seconds) by format
        switch (strtolower($format)) {
            case "seconds":
            case "seconden":
            case "sec":
                $numberOfDays = floor($interval / (60*60*24));
                $intervalSeconds = $interval - ($numberOfDays * 60*60*24);
                break;
            case "minutes":
            case "minuten":
            case "min":
                $numberOfDays = floor($interval / (60*24));
                $intervalSeconds = ($interval - ($numberOfDays * 60*24)) * 60;
                break;
            case "hours":
            case "uren":
            case "u":
                $numberOfDays = floor($interval / 24);
                $intervalSeconds = ($interval - ($numberOfDays * 24)) * (60*60);
                break;
            case "days":
            case "dagen":
            case "d":
                $numberOfDays = $interval;
                $intervalSeconds = 0;
                break;
        }

        // Add (number of) days to date
        $newDate = $this->addWorkDays($date, $numberOfDays, $weekendDays);

        // Add remainder seconds
        if ($intervalSeconds > 0) {
            $interval = new \DateInterval("PT" . $intervalSeconds . "S");
            $newDate->add($interval);
        }

        // Return (new) date
        return $newDate;
    }

    /**
     * Add number of workdays to date
     *
     * @param \DateTime $date
     * @param int $numberOfDays
     * @param array $weekendDays
     * @return \DateTime
     * @throws \Exception
     */
    public function addWorkDays(\DateTime $date, $numberOfDays, $weekendDays = null)
    {
        for ($i = 1; $i <= $numberOfDays; $i++) {
            $date->add(new \DateInterval("P1D"));
            while ($this->isDayOff($date, $weekendDays)) {
                $date->add(new \DateInterval("P1D"));
            }
        }

        // Return
        return $date;
    }

    /**
     * Check if date is day-off
     *
     * @param \DateTime $date
     * @param array $weekendDays
     * @return boolean
     * @throws \Exception
     */
    public function isDayOff (\DateTime $date, $weekendDays = null)
    {
        if (!is_array($weekendDays)) $weekendDays = array("sun");

        if (in_array(strtolower($date->format("D")), $weekendDays)) {
            return true;
        } else {
            // Check Easter (Pasen)
            $easter = new \DateTime();
            $easter->setTimestamp(easter_date($date->format("Y")));
            if ($date == $easter) return true;
            $easterMonday = clone $easter;
            $easterMonday->add(new \DateInterval('P1D'));
            if ($date == $easterMonday) return true;

            // Check Ascension Day (Hemelvaartsdag)
            $ascensionDay = clone $easter;
            $ascensionDay->add(new \DateInterVal('P39D'));
            if ($date == $ascensionDay) return true;

            // Check Pentecost (Pinksteren)
            $pentecost = clone $ascensionDay;
            $pentecost->add(new \DateInterVal('P10D'));
            if ($date == $pentecost) return true;
            $pentecostMonday = clone $pentecost;
            $pentecostMonday->add(new \DateInterVal('P1D'));
            if ($date == $pentecostMonday) return true;

            // Check Kingsday (Koningsdag)
            $kingsDay = new \DateTime($date->format("Y") . "-04-27");
            if ($kingsDay->format('D') === 'Sun') {
                $kingsDay->sub(new \DateInterval('P1D'));
            }
            if ($date == $kingsDay) return true;

            // Check Liberation Day (Bevrijdingsdag)
            if (($date->format("Y") % 5) == 0) {
                $liberationDay = new \DateTime($date->format("Y") . "-05-05");
                if ($date == $liberationDay) return true;
            }

            // Check Christmas Days (Kerstmis)
            $christmasDay = new \DateTime($date->format("Y") . "-12-25");
            if ($date == $christmasDay) return true;
            $christmasDay2 = new \DateTime($date->format("Y") . "-12-26");
            if ($date == $christmasDay2) return true;

            // Check New Years Day(Nieuwjaarsdag)
            $newYearsDay = new \DateTime($date->format("Y") . "-01-01");
            if ($date == $newYearsDay) return true;
        }

        // Return
        return false;
    }

}