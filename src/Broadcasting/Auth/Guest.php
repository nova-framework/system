<?php

namespace Nova\Broadcasting\Auth;

use Nova\Auth\UserInterface;
use Nova\Support\Contracts\ArrayableInterface;

use JsonSerializable;


class Guest implements UserInterface, ArrayableInterface, JsonSerializable
{
    /**
     * All of the user's attributes.
     *
     * @var array
     */
    protected $attributes = array();


    /**
     * Create a new Guest User object.
     *
     * @param  string  $id
     * @param  string  $remoteIp
     * @return void
     */
    public function __construct($id, $remoteIp = null)
    {
        $this->attributes = array(
            'id'        => $id,
            'username'  => 'guest',
            'remote_ip' => $remoteIp,
        );
    }

    /**
     * Convert the object instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->attributes['id'];
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        // The guest Users has no password.
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        // The guest Users has no "remember me" token.
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        // The guest Users has no "remember me" token.
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Dynamically access the user's attributes.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    /**
     * Dynamically check if a value is set on the user.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }
}
