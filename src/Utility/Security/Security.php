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
     * Main function to Encrypt
     *
     * @param $string
     * @return string
     */
    public static function encrypt64($string)
    {
        $key = Configure::read("InternalOptions.key");
        self::_validateKey($key,'encrypt64()');
        $hmacSalt = Configure::read("InternalOptions.salt");

        $result = parent::encrypt($string, $key, $hmacSalt);
        $result = base64_encode($result);

        return $result;
    }

    /**
     * Main function to Decrypt
     *
     * @param $string
     * @return bool|string
     */
    public static function decrypt64($string)
    {
        $key = Configure::read("InternalOptions.key");
        self::_validateKey($key,'decrypt64()');
        $hmacSalt = Configure::read("InternalOptions.salt");

        $result = @base64_decode($string);

        if ($result) {
            $result = parent::decrypt($result, $key, $hmacSalt);
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
    public static function encrypt64Url($string)
    {
        $string = self::encrypt64($string);
        $string = self::makeUrlSafe($string);

        return $string;
    }

    /**
     * Decrypt URL safe version
     *
     * @param $string
     * @return bool|string
     */
    public static function decrypt64Url($string)
    {
        $string = self::unmakeUrlSafe($string);
        $string = self::decrypt64($string);

        return $string;
    }

    /**
     * @param string $data to make URL safe
     * @param bool $use_padding If true, the "=" padding at end of the encoded value are kept, else it is removed
     * @return string
     */
    public static function makeUrlSafe($data, $use_padding = false)
    {
        $encoded = strtr($data, '+/', '-_');
        return true === $use_padding ? $encoded : rtrim($encoded, '=');
    }

    /**
     * @param string $data to unmake URL safe
     * @return string
     */
    public static function unmakeUrlSafe($data)
    {
        return strtr($data, '-_', '+/');
    }

    /**
     * Check the encryption key for proper length.
     *
     * @param string $key Key to check.
     * @param string $method The method the key is being checked for.
     * @return void
     * @throws \InvalidArgumentException When key length is null
     */
    protected static function _validateKey($key, $method)
    {
        if ($key === null) {
            throw new InvalidArgumentException(
                sprintf('The %s function relies on Configure::read("InternalOptions.key") returning a valid key.', $method)
            );
        }
    }
}