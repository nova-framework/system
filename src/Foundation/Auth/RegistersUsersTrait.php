<?php

namespace Nova\Foundation\Auth;

use Nova\Foundation\Auth\RedirectsUsersTrait;
use Nova\Http\Request;
use Nova\Support\Facades\Auth;


trait RegistersUsersTrait
{
	use RedirectsUsersTrait;

	/**
	 * Show the application registration form.
	 *
	 * @return \Nova\Http\Response
	 */
	public function getRegister()
	{
		return $this->createView()->shares('title', __d('nova', 'User Registration'));
	}

	/**
	 * Handle a registration request for the application.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return \Nova\Http\Response
	 */
	public function postRegister(Request $request)
	{
		return $this->register($request);
	}

	/**
	 * Handle a registration request for the application.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return \Nova\Http\Response
	 */
	public function register(Request $request)
	{
		$input = $request->all();

		$validator = $this->validator();

		if ($validator->fails()) {
			$this->throwValidationException($request, $validator);
		}

		$user = $this->create($input);

		Auth::guard($this->getGuard())->login($user);

		return Redirect::to($this->redirectPath());
	}

	/**
	 * Get the guard to be used during registration.
	 *
	 * @return string|null
	 */
	protected function getGuard()
	{
		return property_exists($this, 'guard') ? $this->guard : null;
	}
}
