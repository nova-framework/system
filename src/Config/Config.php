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
    protected static $items = array();


    /**
     * Get the registered settings.
     * @return mixed|null
     */
    public static function all()
    {
        return static::$items;
    }

    /**
     * Return true if the key exists.
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        $default = microtime(true);

        return (static::get($key, $default) !== $default);
    }

    /**
     * Get the value.
     *
     * @param string $key
     * @return mixed|null
     */
    public static function get($key, $default = null)
    {
        @list($group, $item) = static::parseKey($key);

        static::load($group);

        if (empty($item)) {
            return isset(static::$items[$group]) ? static::$items[$group] : $default;
        }

        return array_get(static::$items[$group], $item, $default);
    }

    /**
     * Set the value.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        @list($group, $item) = static::parseKey($key);

        if (empty($item)) {
            static::$items[$group] = $value;
        } else {
            static::load($group);

            array_set(static::$items[$group], $item, $value);
        }
    }

    protected static function load($group)
    {
        if (isset(static::$items[$group])) return;

        //
        $path = APPDIR .'Config' .DS .ucfirst($group) .'.php';

        if (is_readable($path)) {
            return static::$items[$group] = include $path;
        }

        return static::$items[$group] = array();
    }

    /**
     * Parse a key into group, and item.
     *
     * @param  string  $key
     * @return array
     */
    protected static function parseKey($key)
    {
        $segments = explode('.', $key);

        $group = array_shift($segments);

        $segments = implode('.', $segments);

        return array($group, $segments);
    }
}
