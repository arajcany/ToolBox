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
     * Opposite of makeStartsWith().
     * If a string starts with the passed in needle, remove it from the beginning of a string.
     *
     * @param string $string
     * @param string $startsWith
     * @return string
     */
    public static function unmakeStartsWith(string $string = "", string $startsWith = ""): string
    {
        if (self::startsWith($string, $startsWith) === true) {
            return substr_replace($string, '', 0, strlen($startsWith));
        }

        return $string;
    }

    /**
     * Opposite of makeEndsWith().
     * If a string ends with the passed in needle, remove it from the end of a string.
     *
     * @param string $string
     * @param string $endsWith
     * @return string
     * @internal param string $startsWith
     */
    public static function unmakeEndsWith(string $string = "", string $endsWith = ""): string
    {
        if (self::endsWith($string, $endsWith) === true) {
            return substr_replace($string, '', -strlen($endsWith), strlen($endsWith));
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

    /**
     * Make a string end with either a backward or forward trailing slash (i.e. for directory)
     * Will look at the string and determine the most common slash and then use that.
     * Replace or add as necessary.
     *
     * @param string $string
     * @return string
     * @internal param string $startsWith
     */
    public static function makeDirectoryTrailingSmartSlash(string $string = ""): string
    {
        $backCount = substr_count($string, "\\");
        $forwardCount = substr_count($string, "/");

        if ($backCount >= $forwardCount) {
            return self::makeDirectoryTrailingBackwardSlash($string);
        } else {
            return self::makeDirectoryTrailingForwardSlash($string);
        }
    }

    /**
     * Normalise the slashes in the given string
     * \\localhost/FFC_Data     =>      \\localhost\FFC_Data
     * c:\tmp\come/dir          =>      c:\tmp\come\dir
     *
     * NOTE: does not add a trailing slash
     *
     * @param string $string
     * @param string $mode
     * first=> normalise based on first encountered slash type.
     * count=> normalise based on most common slash type. If count is the same, fall back to 'first' mode.
     * @return string
     * @internal param string $startsWith
     */
    public static function normaliseSlashes(string $string = "", string $mode = 'first'): string
    {

        if (strtolower($mode) === 'count') {
            $backCount = substr_count($string, "\\");
            $forwardCount = substr_count($string, "/");

            if ($backCount > $forwardCount) {
                return str_replace(['\\', '/'], '\\', $string);
            } elseif ($backCount < $forwardCount) {
                return str_replace(['\\', '/'], '/', $string);
            } else {
                //same number of each slash type, fall back to first
                $mode = 'first';
            }
        }

        if (strtolower($mode) === 'first') {
            $firstSlashType = null;
            foreach (str_split($string) as $character) {
                if ($character === "\\" || $character === "/") {
                    $firstSlashType = $character;
                    break;
                }
            }

            if (!$firstSlashType) {
                return $string;
            }

            return str_replace(['\\', '/'], $firstSlashType, $string);
        }

        return $string;
    }

    /**
     * Remove repeating delimiters.
     *
     * @param $string
     * @param string[] $delimList
     * @return string
     */
    public static function removeRepeatingDelimiters($string, array $delimList = [',', '-', '_', '|']): string
    {
        $currentStringLength = strlen($string);
        $newStringLength = 0;

        while ($currentStringLength !== $newStringLength) {
            $currentStringLength = strlen($string);
            foreach ($delimList as $delim) {
                $in = "{$delim}{$delim}";
                $out = "{$delim}";
                $string = str_replace($in, $out, $string);

                $in = "{$delim} {$delim} ";
                $out = "{$delim} ";
                $string = str_replace($in, $out, $string);

                $newStringLength = strlen($string);
            }
        }

        return $string;
    }
}