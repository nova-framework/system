<?php

namespace Nova\Foundation\Auth;

use Nova\Foundation\Auth\RedirectsUsersTrait;
use Nova\Foundation\Auth\ThrottlesLoginsTrait;
use Nova\Http\Request;
use Nova\Support\Facades\Auth;
use Nova\Support\Facades\Redirect;
use Nova\Support\Facades\Response;
use Nova\Support\Facades\Validator;
use Nova\Support\Facades\View;
use Nova\Validation\ValidationException;


trait AuthenticatesUsersTrait
{
	use RedirectsUsersTrait, ThrottlesLoginsTrait;

	/**
	 * Show the application login form.
	 *
	 * @return \Nova\Http\Response
	 */
	public function login()
	{
		return $this->createView()
			->shares('title', __d('nova', 'User Login'));
	}

	/**
	 * Handle a login request to the application.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return \Nova\Http\Response
	 */
	public function postLogin(Request $request)
	{
		$this->validateLogin($request);

		if ($this->hasTooManyLoginAttempts($request)) {
			return $this->sendLockoutResponse($request);
		}

		if ($this->attemptLogin($request)) {
			return $this->sendLoginResponse($request);
		}

		$this->incrementLoginAttempts($request);

		return $this->sendFailedLoginResponse($request);
	}

	/**
	 * Validate the user login request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return void
	 */
	protected function validateLogin(Request $request)
	{
		$this->validate($request, array(
			$this->username() => 'required', 'password' => 'required',
		));
	}

	/**
	 * Attempt to log the user into the application.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return bool
	 */
	protected function attemptLogin(Request $request)
	{
		$credentials = $this->credentials($request);

		return Auth::guard($this->getGuard())->attempt($credentials, $request->has('remember'));
	}

	/**
	 * Send the response after the user was authenticated.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  bool  $throttles
	 * @return \Nova\Http\Response
	 */
	protected function sendLoginResponse(Request $request)
	{
		$this->clearLoginAttempts($request);

		//
		$guard = Auth::guard($this->getGuard());

		$response = $this->authenticated($request, $guard->user());

		return  $response ?: Redirect::intended($this->redirectPath());
	}

	/**
	 * The user has been authenticated.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  mixed  $user
	 * @return mixed
	 */
	protected function authenticated(Request $request, $user)
	{
		//
	}

	/**
	 * Get the failed login response instance.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return \Nova\Http\RedirectResponse
	 */
	protected function sendFailedLoginResponse(Request $request)
	{
		$error = __d('nova', 'These credentials do not match our records.');

		$errors = array($this->username() => $error);

		if ($request->json() || $request->expectsJson()) {
			return Response::json($errors, 422);
		}

		return Redirect::back()
			->withInput($request->only($this->username(), 'remember'))
			->withErrors($errors);
	}

	/**
	 * Get the needed authorization credentials from the request.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return array
	 */
	protected function credentials(Request $request)
	{
		return $request->only($this->username(), 'password');
	}

	/**
	 * Log the user out of the application.
	 *
	 * @return \Nova\Http\Response
	 */
	public function logout(Request $request)
	{
		Auth::guard($this->getGuard())->logout();

		$uri = property_exists($this, 'redirectAfterLogout')
			? $this->redirectAfterLogout
			: $this->loginPath();

		return Redirect::to($uri);
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
	public function username()
	{
		return 'username';
	}

	/**
	 * Get the guard to be used during authentication.
	 *
	 * @return string|null
	 */
	protected function getGuard()
	{
		return property_exists($this, 'guard') ? $this->guard : null;
	}
}
