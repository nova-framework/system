<?php
/**
 * ConfigLoader - Implements a Configuration Loader.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date April 12th, 2016
 */
namespace Nova\Config;

use Nova\Config\Config;


class ConfigLoader implements LoaderInterface
{
    /**
     * Load the Configuration Group for the key.
     *
     * @param    string     $group
     * @return     array
     */
    public function load($group)
    {
        return Config::get($group, array());
    }

    /**
     * Set a given configuration value.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value)
    {
        Config::set($key, $value);
    }
}
