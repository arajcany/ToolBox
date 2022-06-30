<?php

namespace arajcany\ToolBox\Utility;


class TextFormatter
{
    /**
     * Checks if the haystack start with the passed in needle
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Checks if the haystack ends with the passed in needle
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function endsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }

    /**
     * Make a string start with the passed in needle - if not already
     *
     * @param string $string
     * @param string $startsWith
     * @return string
     */
    public static function makeStartsWith(string $string = "", string $startsWith = ""): string
    {
        if (self::startsWith($string, $startsWith) === false) {
            $string = $startsWith . $string;
        }

        return $string;
    }

    /**
     * Make a string end with the passed in needle - if not already
     *
     * @param string $string
     * @param string $endsWith
     * @return string
     * @internal param string $startsWith
     */
    public static function makeEndsWith(string $string = "", string $endsWith = ""): string
    {
        if (self::endsWith($string, $endsWith) === false) {
            $string = $string . $endsWith;
        }

        return $string;
    }

    /**
     * Make a string start/end with the passed in needles - if not already
     *
     * @param string $string
     * @param string $startsWith
     * @param string $endsWith
     * @return string
     */
    public static function makeStartsWithAndEndsWith(string $string = "", string $startsWith = "", string $endsWith = ""): string
    {
        return self::makeEndsWith(self::makeStartsWith($string, $startsWith), $endsWith);
    }

    /**
     * Make a string end with a forward trailing slash (i.e. for directory)
     * Replace or add as necessary.
     *
     * @param string $string
     * @return string
     * @internal param string $startsWith
     */
    public static function makeDirectoryTrailingForwardSlash(string $string = ""): string
    {
        $string = rtrim($string, "\\");
        return self::makeEndsWith($string, "/");
    }

    /**
     * Make a string end with a backward trailing slash (i.e. for directory)
     * Replace or add as necessary.
     *
     * @param string $string
     * @return string
     * @internal param string $startsWith
     */
    public static function makeDirectoryTrailingBackwardSlash(string $string = ""): string
    {
        $string = rtrim($string, "/");
        return self::makeEndsWith($string, "\\");
    }
}