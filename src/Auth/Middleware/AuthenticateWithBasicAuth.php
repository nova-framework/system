<?php

namespace Nova\Auth\Middleware;

use Nova\Support\Facades\Auth;

use Closure;


class AuthenticateWithBasicAuth
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  \Closure  $next
	 * @param  string|null  $guard
	 * @return mixed
	 */
	public function handle($request, Closure $next, $guard = null)
	{
		return Auth::guard($guard)->basic() ?: $next($request);
	}
}
