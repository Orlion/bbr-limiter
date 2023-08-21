<?php

namespace Orlion\BbrLimiter;

class Timex
{
    public static function unixMilli(): float
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    public static function unixMicro(): float
    {
        $microseconds = microtime(true);
        return round($microseconds * 1000000);
    }
}