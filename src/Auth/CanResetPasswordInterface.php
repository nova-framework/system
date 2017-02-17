<?php

namespace Nova\Contracts\Auth;


interface CanResetPasswordInterface
{
    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset();
}
