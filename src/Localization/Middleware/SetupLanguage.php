<?php

namespace Nova\Localization\Middleware;

use Nova\Foundation\Application;
use Nova\Support\Facades\Cookie;

use Closure;


class SetupLanguage
{
    /**
     * The application implementation.
     *
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * Create a new middleware instance.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Nova\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Nova\Http\Exception\PostTooLargeException
     */
    public function handle($request, Closure $next)
    {
        $this->updateLocale($request);

        return $next($request);
    }

    /**
     * Update the Application locale.
     *
     * @param  \Nova\Http\Request  $request
     * @return void
     */
    protected function updateLocale($request)
    {
        $session = $this->app['session'];

        if ($session->has('language')) {
            $locale = $session->get('language');
        } else {
            $cookie = PREFIX .'language';

            if (empty($locale = $request->cookie($cookie))) {
                $locale = $this->app['config']->get('app.locale');

                // Store also the current Language in a Cookie lasting five years.
                Cookie::queue($cookie, $locale, Cookie::FIVEYEARS);
            }

            $session->set('language', $locale);
        }

        $this->app['language']->setLocale($locale);
    }
}
