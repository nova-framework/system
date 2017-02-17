<?php

namespace Nova\Foundation\Auth;

use Nova\Http\Request;
use Nova\Mail\Message;
use Nova\Support\Facades\Auth;
use Nova\Support\Facades\Hash;
use Nova\Support\Facades\Password;
use Nova\Support\Facades\Redirect;
use Nova\Support\Facades\View;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


trait ResetsPasswords
{
    use RedirectsUsers;


    /**
     * Display the form to request a password reset link.
     *
     * @return \Nova\Http\Response
     */
    public function getEmail()
    {
        return View::make('Auth/Password')
            ->shares('title', __d('nova', 'Reset Password'));
    }

    /**
     * Send a reset link to the given user.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Http\Response
     */
    public function postEmail(Request $request)
    {
        $this->validate($request, array('email' => 'required|email'));

        $response = Password::sendResetLink($request->only('email'), function (Message $message)
        {
            $message->subject($this->getEmailSubject());
        });

        switch ($response) {
            case Password::REMINDER_SENT:
                return Redirect::back()->withStatus(__d('nova', 'Reset instructions have been sent to your email address'));

            case Password::INVALID_USER:
                return Redirect::back()->withErrors(array('email' => __d('nova', 'We can\'t find a User with that e-mail address.')));
        }
    }

    /**
     * Get the e-mail subject line to be used for the reset link email.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        return property_exists($this, 'subject') ? $this->subject : __d('nova', 'Your Password Reset Link');
    }

    /**
     * Display the password reset view for the given token.
     *
     * @param  string  $token
     * @return \Nova\Http\Response
     */
    abstract public function getReset($token = null)
    {
        if (is_null($token)) {
            throw new NotFoundHttpException;
        }

        return View::make('Auth/Reset')
            ->shares('title', __d('nova', 'Reset Password'))
            ->with('token', $token);
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Http\Response
     */
    public function postReset(Request $request)
    {
        $this->validate($request, array(
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|confirmed|min:6',
        ));

        $credentials = $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );

        $response = Password::reset($credentials, function ($user, $password)
        {
            $this->resetPassword($user, $password);
        });

        // Parse the response.
        switch ($response) {
            case Password::INVALID_PASSWORD:
                $status = __d('users', 'Passwords must be strong enough and match the confirmation.');

                break;
            case Password::INVALID_TOKEN:
                $status = __d('users', 'This password reset token is invalid.');

                break;
            case Password::INVALID_USER:
                $status = __d('users', 'We can\'t find a User with that e-mail address.');

                break;
            case Password::PASSWORD_RESET:
                $status = __d('users', 'You have successfully reset your Password.');

                return Redirect::to($this->redirectPath())->withStatus($status);
        }

        return Redirect::back()->withStatus($status, 'danger');
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Nova\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);

        $user->save();

        Auth::login($user);
    }
}
