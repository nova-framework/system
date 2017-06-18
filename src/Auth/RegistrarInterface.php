<?php

namespace Nova\Auth;


interface RegistrarInterface
{
	/**
	 * Get a validator for an incoming registration request.
	 *
	 * @param  array  $data
	 * @return \Nova\Validation\Validator
	 */
	public function validator(array $data);

	/**
	 * Create a new user instance after a valid registration.
	 *
	 * @param  array  $data
	 * @return \Nova\Auth\Contracts\UserInterface
	 */
	public function create(array $data);
}
