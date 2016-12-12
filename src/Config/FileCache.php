<?php
/**
 * FileCache - Implements a simple File Cache for the Configuration.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date December 12th, 2016
 */

namespace Nova\Config;


class FileCache
{
    /**
     * Wheter or not the caching is active.
     *
     * @var bool
     */
    protected $active = true;

    /**
     * The path to the cache files.
     *
     * @var string
     */
    protected $path;


    /**
     * Contruct a new FileCache instance.
     * @return void
     */
    public function __construct()
    {
        // Setup the cache files path.
        $this->path = STORAGE_PATH .'Cache' .DS;

        if (defined(CONFIG_CACHE)) {
            // Setup wheter or not the caching is active.
            $this->active = (CONFIG_CACHE === true);
        }
    }

    /**
     * Wheter or not the caching is active.
     *
     * @return bool
     */
    public function active()
    {
        return $this->active;
    }

    /**
     * The function to check if there is cached data.
     * @param  string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
    }

    /**
     * The function to fetch data returns null on failure.
     * @param  string $key
     *
     * @return array|void
     */
    function get($key)
    {
        $filename = $this->getFileName($key);

        if ($this->active() && is_readable($filename)) {
            // Retrieve the file contents.
            $content = file_get_contents($filename);

            // Unserialize the data from content.
            $data = @unserialize($content);

            if (is_array($data) && ! empty($data)) {
                // An valid data array was found; retrieve the TTL.
                $ttl = array_shift($data);

                if (time() < $ttl) {
                    // The cached data was not expired; return it.
                    return array_shift($data);
                }
            }
        }

        // The caching is disabled, unserializing failed or data expired.

        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * This is the function you store information with.
     * @param  string $key
     * @param  mixed  $data
     * @param  int    $ttl
     *
     * @return void
     */
    function put($key, $data, $ttl = 3600)
    {
        $filename = $this->getFileName($key);

        if ($this->active()) {
            // Serializing along with the TTL.
            $data = array(time() + $ttl, $data);

            // Store the serialized information on the cache file.
            file_put_contents($filename, serialize($data), LOCK_EX);
        } else if (file_exists($filename)) {
            // The caching is disabled; just remove the cache file.
            unlink($filename);
        }
    }

    /**
     * The function to remove cached data.
     * @param  string $key
     *
     * @return void
     */
    public function forget($key)
    {
        $filename = $this->getFileName($key);

        if (! file_exists($filename)) return false;

        unlink($filename);

        return true;
    }

    /**
     * General function to find the filename for a certain key.
     * @param  string $key
     *
     * @return string
     */
    protected function getFileName($key)
    {
        return $this->path .'config_' .sha1($key) .'.txt';
    }

}
