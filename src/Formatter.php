<?php

namespace arajcany\ToolBox;


class Formatter
{
    public static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

    public static function makeStartsWith($string = "", $startsWith = "")
    {
        if (self::startsWith($string, $startsWith) === false) {
            $string = $startsWith . $string;
        }

        return $string;
    }

    public static function makeEndsWith($string = "", $endsWith = "")
    {
        if (self::endsWith($string, $endsWith) === false) {
            $string = $string . $endsWith;
        }

        return $string;
    }

    public static function makeStartsWithAndEndsWith($string = "", $startsWith = "", $endsWith = "")
    {
        if (self::endsWith($string, $endsWith) === false) {
            $string = $string . $endsWith;
        }

        if (self::startsWith($string, $startsWith) === false) {
            $string = $startsWith . $string;
        }

        return $string;
    }
}