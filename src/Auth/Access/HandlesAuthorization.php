<?php

namespace Nova\Auth\Access;

use Nova\Auth\Access\Response;
use Nova\Auth\Access\UnauthorizedException;


trait HandlesAuthorization
{
    /**
     * Create a new access response.
     *
     * @param  string|null  $message
     * @return \Nova\Auth\Access\Response
     */
    protected function allow($message = null)
    {
        return new Response($message);
    }

    /**
     * Throws an unauthorized exception.
     *
     * @param  string  $message
     * @return void
     *
     * @throws \Nova\Auth\Access\UnauthorizedException
     */
    protected function deny($message = null)
    {
        $message = $message ?: __d('nova', 'This action is unauthorized.');

        throw new UnauthorizedException($message);
    }
}
