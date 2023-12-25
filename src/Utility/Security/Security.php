<?php

namespace arajcany\ToolBox\Utility\Security;

use Cake\Core\Configure;
use Cake\Utility\Security as CakeSecurity;
use InvalidArgumentException;

/**
 * Class Security
 * Mainly used to make sure that encrypt/decrypt functions are url safe
 *
 * @package App\Utility\Security
 */
class Security extends CakeSecurity
{
    /**
     * Generate a secure GUID/UUID
     *
     * @return string
     */
    public static function guid(): string
    {
        $randomString = Security::randomString(2048);
        $format = 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
        $formatParts = explode('-', $format);

        $guid = [];
        $counter = 0;
        foreach ($formatParts as $formatPart) {
            $len = strlen($formatPart);
            $randomStringExtract = substr($randomString, $counter, $len);
            $guid[] = $randomStringExtract;

            $counter += $len;
        }

        return implode("-", $guid);
    }

    /**
     * Check if the passed in string meets criteria of a uuid or sha1 checkusum.
     *
     * @param $input
     * @return bool
     */
    public static function isValidUuidOrSha1($input) {
        //is uid format - limit 4 repeating characters
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $input)) {
            return true;
        }

        //is SHA-1 checksum - limit 4 repeating characters
        if (preg_match('/^[0-9a-fA-F]{5,40}$/', $input) && !preg_match('/(.)\1{4,}/', $input)) {
            // Check for proper SHA-1 checksum length and no more than 4 repeating characters
            return true;
        }

        return false;
    }

    /**
     * Generate string of letters and numbers that can be used as a "Personalised URL" (PURL).
     * PURLs are a slightly better looking than guids for use as unique URLs.
     * They come at the cost of uniqueness. Chance of collisions of you reduce the length.
     *
     * @param int $purlLength
     * @return string
     */
    public static function purl(int $purlLength = 8): string
    {
        $randomString = Security::randomString(2048);
        $purlLength = max(8, $purlLength);
        return substr(base_convert(sha1($randomString), 16, 36), 0, $purlLength);
    }

    /**
     * Main function to Encrypt
     *
     * @param $string
     * @return string
     */
    public static function encrypt64($string): string
    {
        $key = Configure::read("InternalOptions.key");
        self::_validateKey($key, 'encrypt64()');
        $hmacSalt = Configure::read("InternalOptions.salt");

        $result = parent::encrypt($string, $key, $hmacSalt);
        $result = base64_encode($result);

        return $result;
    }

    /**
     * Main function to Decrypt
     *
     * @param $string
     * @return string
     */
    public static function decrypt64($string): string
    {
        $key = Configure::read("InternalOptions.key");
        self::_validateKey($key, 'decrypt64()');
        $hmacSalt = Configure::read("InternalOptions.salt");

        $result = @base64_decode($string);

        if ($result) {
            $result = parent::decrypt($result, $key, $hmacSalt);
            if ($result == null) {
                $result = '';
            }
        } else {
            $result = '';
        }

        return $result;
    }

    /**
     * Encrypt URL safe version
     *
     * @param $string
     * @return string
     */
    public static function encrypt64Url($string): string
    {
        return self::makeUrlSafe(self::encrypt64($string));
    }

    /**
     * Decrypt URL safe version
     *
     * @param $string
     * @return string
     */
    public static function decrypt64Url($string): string
    {
        $string = self::decrypt64(self::unmakeUrlSafe($string));

        if (empty($string)) {
            $string = '';
        }
        return $string;
    }

    /**
     * @param string $data to make URL safe
     * @param bool $use_padding If true, the "=" padding at end of the encoded value are kept, else it is removed
     * @return string
     */
    public static function makeUrlSafe(string $data, bool $use_padding = false): string
    {
        $encoded = strtr($data, '+/', '-_');
        return true === $use_padding ? $encoded : rtrim($encoded, '=');
    }

    /**
     * @param string $data to unmake URL safe
     * @return string
     */
    public static function unmakeUrlSafe(string $data): string
    {
        return strtr($data, '-_', '+/');
    }

    /**
     * Check the encryption key for proper length.
     *
     * @param string $key Key to check.
     * @param string $method The method the key is being checked for.
     * @return void
     * @throws InvalidArgumentException When key length is null
     */
    protected static function _validateKey(string $key, string $method)
    {
        if ($key === null) {
            throw new InvalidArgumentException(
                sprintf('The %s function relies on Configure::read("InternalOptions.key") returning a valid key.', $method)
            );
        }
    }
}