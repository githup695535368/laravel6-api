<?php namespace Constants;

/**
 *
 * Class HasEnumsTrait
 * @package Constants
 * @link    http://themsaid.com/better-readability-enumerators-php-20160420/
 */
trait HasEnumsTrait
{
    /**
     * 兼容老格式
     * @param string $prefix
     * @return array
     */
    public static function constants(string $prefix)
    {
        return self::enums($prefix);
    }

    /**
     * The array of enumerators of a given group.
     *
     * @param  null|string $group
     * @return  array
     */
    public static function enums($group = null)
    {
        $constants = (new \ReflectionClass(get_called_class()))->getConstants();

        if ($group) {
            $group = strtoupper($group) . '_';
            return array_filter($constants, function ($key) use ($group) {
                return strpos($key, $group) === 0;
            }, ARRAY_FILTER_USE_KEY);
        }

        return $constants;
    }

    /**
     * Check if the given value is valid within the given group.
     *
     * @param  mixed $value
     * @param  null|string $group
     * @return  bool
     */
    public static function isValidEnumValue($value, $group = null)
    {
        $constants = static::enums($group);

        return in_array($value, $constants);
    }

    /**
     * Check if the given key exists.
     *
     * @param  mixed $key
     * @return  bool
     */
    public static function isValidEnumKey($key)
    {
        return array_key_exists($key, static::enums());
    }
}
