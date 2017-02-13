<?php

namespace Nova\Cookie\Middleware;

use Nova\Cookie\CookieJar;

use Closure;


class AddQueuedCookiesToResponse
{
    /**
     * The cookie jar instance.
     *
     * @var \Nova\Cookie\CookieJar
     */
    protected $cookies;

    /**
     * Create a new CookieQueue instance.
     *
     * @param  \Nova\Cookie\CookieJar  $cookies
     * @return void
     */
    public function __construct(CookieJar $cookies)
    {
        $this->cookies = $cookies;
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
        $response = $next($request);

        foreach ($this->cookies->getQueuedCookies() as $cookie) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
