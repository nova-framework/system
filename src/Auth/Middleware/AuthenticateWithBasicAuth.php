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
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $this->auth->basic() ?: $next($request);
    }
}
