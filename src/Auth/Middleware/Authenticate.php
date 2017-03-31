<?php

namespace Nova\Auth\Middleware;

use Nova\Support\Facades\Auth;
use Nova\Support\Facades\Config;
use Nova\Support\Facades\Response;
use Nova\Support\Facades\Redirect;

use Closure;


class Authenticate
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
        $guard = $guard ?: Config::get('auth.defaults.guard', 'web');

        if (Auth::guard($guard)->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return Response::make('Unauthorized.', 401);
            }

            // Get the Guard's paths from configuration.
            $paths = Config::get("auth.guards.{$guard}.paths", array(
                'authorize' => 'auth/login',
                'nonintend' => array(
                    'auth/logout',
                ),
            );

            if (in_array($request->path(), $paths['nonintend'])) {
                return Redirect::to($paths['authorize']);
            } else {
                return Redirect::guest($paths['authorize']);
            }
        }

        return $next($request);
    }
}
