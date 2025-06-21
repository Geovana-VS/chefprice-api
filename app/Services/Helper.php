<?php

namespace App\Services;

class Helper
{
    /**
     * Truncates a float to a given number of decimal places.
     *
     * @param float $number The number to truncate.
     * @param int $places The number of decimal places.
     * @return float
     */
    public static function truncate_float($number, $places)
    {
        $power = pow(10, $places);
        if ($number > 0) {
            return floor($number * $power) / $power;
        } else {
            return ceil($number * $power) / $power;
        }
    }
}
