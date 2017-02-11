<?php
/**
 * Config - manage the system wide configuration parameters.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date April 12th, 2016
 */

namespace Nova\Config;


class Config
{
    /**
     * @var array
     */
    protected static $options = array();


    /**
     * Get the registered settings.
     * @return array
     */
    public static function all()
    {
        return static::$options;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        $default = microtime(true);

        if (static::get($key, $default) !== $default) {
            return true;
        }

        return false;
    }

    /**
     * Get the value.
     * @param string $key
     * @return mixed|null
     */
    public static function get($key, $default = null)
    {
        return array_get(static::$options, $key, $default);
    }

    /**
     * Set the value.
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        array_set(static::$options, $key, $value);
    }
}
