<?php

namespace Nova\Auth;

use Nova\Auth\AuthenticationException;
use Nova\Auth\UserInterface;


/**
 * These methods are typically the same across all guards.
 */
trait GuardHelpersTrait
{
    /**
     * The currently authenticated user.
     *
     * @var \Nova\Auth\UserInterface
     */
    protected $user;

    /**
     * The user provider implementation.
     *
     * @var \Nova\Auth\UserProviderInterface
     */
    protected $provider;


    /**
     * Determine if the current user is authenticated.
     *
     * @return \Nova\Auth\Contracts\UserInterface
     *
     * @throws \Nova\Auth\AuthenticationException
     */
    public function authenticate()
    {
        if (! is_null($user = $this->user())) {
            return $user;
        }

        throw new AuthenticationException;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|null
     */
    public function id()
    {
        if (! is_null($user = $this->user())) {
            return $user->getAuthIdentifier();
        }
    }

    /**
     * Set the current user.
     *
     * @param  \Nova\Auth\UserInterface  $user
     * @return $this
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }
}
