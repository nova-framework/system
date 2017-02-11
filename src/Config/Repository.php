<?php
/**
 * Repository - Implements a Configuration Repository.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date April 12th, 2016
 */

namespace Nova\Config;


class Repository implements \ArrayAccess
{
    /**
     * The loader implementation.
     *
     * @var \Nova\Config\LoaderInterface
     */
    protected $loader;

    /**
     * The current environment.
     *
     * @var string
     */
    protected $environment;

    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = array();


    /**
     * Create a new repository instance.
     *
     * @param  \Nova\Config\LoaderInterface  $loader
     * @param  string  $environment
     *
     * @return void
     */
    function __construct(LoaderInterface $loader, $environment)
    {
        $this->loader = $loader;

        $this->environment = $environment;
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

        return $this->get($key, $default) !== $default;
    }

    /**
     * Determine if a configuration group exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGroup($key)
    {
        list($group, $item) = $this->parseKey($key);

        return $this->loader->exists($group);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        list($group, $item) = $this->parseKey($key);

        $this->load($group);

        if (empty($item)) {
            return $this->items[$group];
        }

        return array_get($this->items[$group], $item, $default);
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
        list($group, $item) = $this->parseKey($key);

        $this->load($group);

        if (empty($item)) {
            $this->items[$group] = $value;
        } else {
            array_set($this->items[$group], $item, $value);
        }
    }

    /**
     * Load the configuration group for the key.
     *
     * @param    string     $group
     * @return     void
     */
    public function load($group)
    {
        $env = $this->environment;

        //
        if (isset($this->items[$group])) return;

        $this->items[$group] = $this->loader->load($env, $group);
    }

    /**
     * Parse a key into group, and item.
     *
     * @param  string  $key
     * @return array
     */
    public function parseKey($key)
    {
        $segments = explode('.', $key);

        $group = head($segments);

        if (count($segments) == 1) {
            return array($group, null);
        }

        $item = implode('.', array_slice($segments, 1));

        return array($group, $item);
    }

    /**
     * Get the loader manager instance.
     *
     * @return \Nova\Config\LoaderInterface
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Set the loader implementation.
     *
     * @param  \Nova\Config\LoaderInterface  $loader
     * @return void
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Get all of the configuration items.
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
