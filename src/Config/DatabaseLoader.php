<?php
/**
 * DatabaseLoader - Implements a Configuration Loader for Database storage.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date April 12th, 2016
 */

namespace Nova\Config;

use Nova\Config\FileCache;
use Nova\Database\Connection;


class DatabaseLoader implements LoaderInterface
{
    /**
     * The Config Cache instance.
     *
     * @var \Nova\Config\FileCache
     */
    protected $cache = null;

    /**
     * The Database Connection instance.
     *
     * @var \Nova\Database\Connection
     */
    protected $connection;

    /**
     * The Database Table.
     *
     * @var string
     */
    protected $table = 'options';

    /**
     * Create a new fileloader instance.
     *
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        // Setup the File Cache instance.
        $this->cache = new FileCache();
    }

    /**
     * Set the database table.
     *
     * @param string
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Create a new database query
     *
     * @return \Nova\Database\Query
     */
    public function newQuery()
    {
        return $this->connection->table($this->table);
    }

    /**
     * Load the Configuration Group for the key.
     *
     * @param    string     $group
     * @return     array
     */
    public function load($group)
    {
        $items = $this->cache->get($group);

        if (! is_null($items)) return $items;

        // The current Group's data is not cached.
        $items = $this->fetch($group);

        $this->cache->put($group, $items, 24 * 3600);

        return $items;
    }

    /**
     * Set a given configuration value.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $values)
    {
        @list($group, $item) = $this->parseKey($key);

        // Clear the cached information.
        $this->cache->forget($group);

        // Update the information on Database.
        if (! empty($item)) {
            $this->update($group, $item, $values);
        } else if (is_array($values)) {
            foreach ($values as $item => $value) {
                $this->update($group, $item, $value);
            }
        } else {
            throw new \InvalidArgumentException("Invalid value for the key: " .$key);
        }
    }

    /**
     * Fetch a group items from the database.
     *
     * @param  string  $key
     * @return array
     */
    protected function fetch($group)
    {
        $items = array();

        $results = $this->newQuery()
            ->where('group', $group)
            ->get(array('item', 'value'));

        foreach ($results as $result) {
            $result = (array) $result;

            $key = $result['item'];

            // Insert the option on list.
            $items[$key] = $this->maybeDecode($result['value']);
        }

        return $items;
    }

    /**
     * Update or Insert the given configuration value.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    protected function update($group, $item, $value)
    {
        $value = $this->maybeEncode($value);

        $id = $this->newQuery()
            ->where('group', $group)
            ->where('item', $item)
            ->pluck('id');

        if (is_null($id)) {
            $this->newQuery()
                ->insert(compact('group', 'item', 'value'));
        } else {
            $this->newQuery()->where('id', $id)
                ->limit(1)
                ->update(compact('value'));
        }
    }

    /**
     * Parse a key into group, and item.
     *
     * @param  string  $key
     * @return array
     */
    protected function parseKey($key)
    {
        $segments = explode('.', $key);

        $group = array_shift($segments);

        $segments = implode('.', $segments);

        return array($group, $segments);
    }

    /**
     * Decode value only if it was encoded to JSON.
     *
     * @param  string  $original
     * @param  bool    $assoc
     * @return mixed
     */
    protected function maybeDecode($original, $assoc = true)
    {
        if (is_numeric($original)) return $original;

        $data = json_decode($original, $assoc);

        return (is_object($data) || is_array($data)) ? $data : $original;
    }

    /**
     * Encode data to JSON, if needed.
     *
     * @param  mixed  $data
     * @return mixed
     */
    protected function maybeEncode($data)
    {
        if (is_array($data) || is_object($data)) {
            return json_encode($data);
        }

        return $data;
    }

}
