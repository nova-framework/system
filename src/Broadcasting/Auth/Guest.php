<?php

namespace Nova\Broadcasting\Auth;

use Nova\Auth\UserInterface;
use Nova\Support\Contracts\ArrayableInterface;

use JsonSerializable;


class Guest implements UserInterface, ArrayableInterface, JsonSerializable
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $username = 'guest';


    /**
     * Create a new Guest User object.
     *
     * @param  string  $id
     * @param  string  $remoteIp
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Convert the object instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'       => $this->id,
            'username' => $this->username,
        );
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
        return $this->id;
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
        //
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        //
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
}
