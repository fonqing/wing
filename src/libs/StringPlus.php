<?php

namespace wing\libs;
use Closure;

class StringPlus
{
    /**
     * Encode string
     *
     * @param mixed $string
     * @return mixed|string
     */
    public static function htmlEncode(mixed $string): mixed
    {
        if (!is_string($string)) {
            return $string;
        }
        $string = rawurldecode(trim($string));
        $string = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $string);
        do {
            $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string, -1, $count);
        } while ($count);
        return htmlentities(trim($string), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate random string
     *
     * @param string $type
     * @param int $length
     * @return string
     */
    public static function random(string $type = 'captcha', int $length = 4): string
    {
        $characters = match ($type) {
            'captcha' => 'ACEFGHJKLMNPQRTUVWXY345679',
            'number' => '0123456789',
            'letters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            default => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
        };
        return (string) substr(str_shuffle(str_repeat($characters, $length)), 0, $length);
    }

    /**
     * function str_contains() is available since PHP 8.0.0
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function strContains(string $haystack, string $needle): bool
    {
        if (function_exists('str_contains')) {
            return str_contains($haystack, $needle);
        }
        return strpos($haystack, $needle) !== false;
    }

    public static function strStartsWith(string $haystack, string $needle): bool
    {
        if (function_exists('str_starts_with')) {
            return str_starts_with($haystack, $needle);
        } else {
            return substr($haystack, 0, strlen($needle)) === $needle;
        }
    }

    public static function strEndsWith(string $haystack, string $needle): bool
    {
        if (function_exists('str_ends_with')) {
            return str_ends_with($haystack, $needle);
        } else {
            return substr($haystack, -strlen($needle)) === $needle;
        }
    }

    /**
     * Generate unique id
     *
     * @param string $prefix
     * @param int $length
     * @return string
     */
    public static function uniqueId(string $prefix = '', int $length = 13): string
    {
        if (function_exists("random_bytes")) {
            try {
                $bytes = random_bytes(ceil($length / 2));
            } catch (\Exception $e) {
                return uniqid($prefix);
            }
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        } else {
            return uniqid($prefix);
        }
        return $prefix . substr(bin2hex($bytes), 0, $length);
    }

    /**
     * join array elements with a specific separator
     * 
     * @param array $args
     * @param string $separator
     * @return string
     */
    public static function join(array $args, string $separator = ''): string
    {
        return implode($separator, $args);
    }

    /**
     * Split string into to parts by a specific character
     *
     * @param string $string
     * @param string $splitter
     * @return array|string
     */
    public static function split(string $string, string $splitter): array|string
    {
        if (empty($splitter)) {
            return str_split($string);
        }
        $pos = strpos($string, $splitter);
        if ($pos === false) {
            return $string;
        }
        return [
            substr($string, 0, $pos),
            substr($string, $pos + 1)
        ];
    }

    /**
     * Do callback when condition is true
     * 
     * @param mixed $condition
     * @param callable $yes
     * @param mixed $no
     * @return mixed
     */
    public static function doWhen(mixed $condition, callable $yes, mixed $no = null)
    {
        if (is_callable($condition) || $condition instanceof Closure) {
            $condition = call_user_func($condition);
        }
        return $condition ? call_user_func($yes) :
            (($no && is_callable($no)) ? call_user_func($no) : null);
    }

    /**
     * Get the first not empty value of an array or a specific key of an array
     * 
     * @param array $array
     * @param string $key
     * @return mixed
     */
    public static function getNotEmptyValue(array $array, string $key = '')
    {
        if (empty($key)) {
            foreach ($array as $v) {
                if (!empty($v)) {
                    return $v;
                }
            }
        } else {
            foreach ($array as $v) {
                if (!empty($v[$key])) {
                    return $v[$key];
                }
            }
        }
        return null;
    }
}