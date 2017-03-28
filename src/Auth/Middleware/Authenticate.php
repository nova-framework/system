<?php

namespace Nova\Auth\Middleware;

use Nova\Support\Facades\Auth;
use Nova\Support\Facades\Response;
use Nova\Support\Facades\Redirect;

use Closure;


class Authenticate
{
    /**
     * The URI where are redirected the Guests.
     *
     * @var string
     */
    protected $guestUri = 'auth/login';

    /**
     * The URI where are logged out the authenticated Users.
     *
     * @var string
     */
    protected $crazyUri = 'auth/logout';


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
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return Response::make('Unauthorized.', 401);
            } else if ($request->path() == $this->crazyUri) {
                return Redirect::to($this->guestUri);
            } else {
                return Redirect::guest($this->guestUri);
            }
        }

        return $next($request);
    }
}
