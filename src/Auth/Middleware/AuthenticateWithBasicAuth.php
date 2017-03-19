<?php

namespace Nova\Auth\Middleware;

use Nova\Foundation\Application;

use Closure;


class AuthenticateWithBasicAuth
{
    /**
     * The guard instance.
     *
     * @var \Nova\Auth\Guard;
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Nova\Foundation\Application  $auth
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->auth = $app['auth'];
    }

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
        return $this->auth->guard($guard)->basic() ?: $next($request);
    }
}
