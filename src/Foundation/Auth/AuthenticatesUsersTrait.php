<?php

namespace Nova\Foundation\Auth;

use Nova\Http\Request;
use Nova\Support\Facades\Auth;
use Nova\Support\Facades\Redirect;
use Nova\Support\Facades\View;


trait AuthenticatesUsersTrait
{
    use RedirectsUsersTrait;


    /**
     * Show the application login form.
     *
     * @return \Nova\Http\Response
     */
    public function getLogin()
    {
        View::share('title', __d('nova', 'User Login'));

        if (View::exists('Auth/Authenticate')) {
            return View::make('Auth/Authenticate');
        }

        return View::make('Auth/Login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Http\Response
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, array(
            $this->loginUsername() => 'required', 'password' => 'required',
        ));

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        $throttles = $this->isUsingThrottlesLoginsTrait();

        if ($throttles && $this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->getCredentials($request);

        if (Auth::attempt($credentials, $request->has('remember'))) {
            return $this->handleUserWasAuthenticated($request, $throttles);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        return Redirect::to($this->loginPath())
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors(array(
                $this->loginUsername() => $this->getFailedLoginMessage(),
            ));
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Nova\Http\Request  $request
     * @param  bool  $throttles
     * @return \Nova\Http\Response
     */
    protected function handleUserWasAuthenticated(Request $request, $throttles)
    {
        if ($throttles) {
            $this->clearLoginAttempts($request);
        }

        if (method_exists($this, 'authenticated')) {
            return $this->authenticated($request, Auth::user());
        }

        return Redirect::intended($this->redirectPath());
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Nova\Http\Request  $request
     * @return array
     */
    protected function getCredentials(Request $request)
    {
        return $request->only($this->loginUsername(), 'password');
    }

    /**
     * Get the failed login message.
     *
     * @return string
     */
    protected function getFailedLoginMessage()
    {
        return __d('nova', 'These credentials do not match our records.');
    }

    /**
     * Log the user out of the application.
     *
     * @return \Nova\Http\Response
     */
    public function getLogout()
    {
        Auth::logout();

        return Redirect::to(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
    }

    /**
     * Get the path to the login route.
     *
     * @return string
     */
    public function loginPath()
    {
        return property_exists($this, 'loginPath') ? $this->loginPath : '/auth/login';
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function loginUsername()
    {
        return property_exists($this, 'username') ? $this->username : 'email';
    }

    /**
     * Determine if the class is using the ThrottlesLogins trait.
     *
     * @return bool
     */
    protected function isUsingThrottlesLoginsTrait()
    {
        return in_array(
            ThrottlesLogins::class, class_uses_recursive(get_class($this))
        );
    }
}
