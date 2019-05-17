<?php

namespace AtpCore;

class Date
{

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