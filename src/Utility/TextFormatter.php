<?php

namespace arajcany\ToolBox\Utility;


use function PHPUnit\Framework\stringContains;

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

    /**
     * Strip text between the tags (+ the tags themselves).
     * Default is to keep the same whitespace - keep char count and preserve line breaks.
     *
     * Set $replacement = '' to squash down the text
     * Set $preserveLineBreaks = false to remove line breaks
     *
     *
     * @param string $string
     * @param string $startTag
     * @param string $endTag
     * @param string $replacement
     * @param bool $preserveLineBreaks
     * @return string
     */
    public static function stripBetweenTags(string $string, string $startTag, string $endTag, string $replacement = ' ', bool $preserveLineBreaks = true): string
    {
        $foundStrings = self::findBetweenTags($string, $startTag, $endTag);

        $strippedString = $string;
        foreach ($foundStrings as $foundString) {
            $inString = $startTag . $foundString . $endTag;
            if ($preserveLineBreaks) {
                $outString = preg_replace("/[^\n\r]/", $replacement, $inString);
            } else {
                $outString = str_pad('', strlen($inString), $replacement);
            }
            $strippedString = str_replace($inString, $outString, $strippedString);
        }

        return $strippedString;
    }

    /**
     * Find the text between tags.
     *
     * Returns an array of found string. The found string will not have the start/end tags.
     *
     * Uses an algorithm to find the matching start/end tag (i.e. for nested tags).
     *
     * @param string $string
     * @param string $startTag
     * @param string $endTag
     * @return string[]
     */
    public static function findBetweenTags(string $string, string $startTag, string $endTag): array
    {
        if (!str_contains($string, $startTag) || !str_contains($string, $endTag)) {
            return [];
        }

        $countStartTags = substr_count($string, $startTag);
        $countEndTags = substr_count($string, $endTag);

        if ($countStartTags !== $countEndTags) {
            return [];
        }

        $braceLeft = 'CURLY_BRACE_LEFT';
        $braceRight = 'CURLY_BRACE_RIGHT';
        $startTagTemp = '{';
        $endTagTemp = '}';
        $string = str_replace($startTagTemp, $braceLeft, $string);
        $string = str_replace($endTagTemp, $braceRight, $string);
        $string = str_replace($startTag, $startTagTemp, $string);
        $string = str_replace($endTag, $endTagTemp, $string);

        $allStartingPositions = self::strpos_all($string, $startTagTemp);
        $allEndingPositions = self::strpos_all($string, $endTagTemp);

        $foundStrings = [];
        foreach ($allStartingPositions as $s => $startPosition) {
            $partialString = substr($string, $startPosition);

            $tagIndicator = 0;
            foreach ((str_split($partialString)) as $e => $char) {
                $currentStart = 0; //is always 0 as wer are dealing with the partial string
                if ($char === $startTagTemp) {
                    $tagIndicator++;
                }
                if ($char === $endTagTemp) {
                    $currentEnd = $e;
                    $tagIndicator--;
                    if ($tagIndicator === 0 && $e !== 0) {
                        $foundString = substr($partialString, $currentStart + 1, $currentEnd - 1);
                        $foundString = str_replace($startTagTemp, $startTag, $foundString);
                        $foundString = str_replace($endTagTemp, $endTag, $foundString);
                        $foundString = str_replace($braceLeft, $startTagTemp, $foundString);
                        $foundString = str_replace($braceRight, $endTagTemp, $foundString);
                        $foundStrings[] = $foundString;
                        break;
                    }
                }
            }
        }

        return $foundStrings;
    }

    /**
     * @param $haystack
     * @param $needle
     * @return array
     */
    private static function strpos_all($haystack, $needle): array
    {
        $offset = 0;
        $allPos = array();
        while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
            $offset = $pos + 1;
            $allPos[] = $pos;
        }

        return $allPos;
    }
}