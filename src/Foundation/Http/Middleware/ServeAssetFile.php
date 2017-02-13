<?php

namespace Nova\Foundation\Http\Middleware;

use Nova\Foundation\Application;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Closure;


class ServeAssetFile
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
     */
    public function handle($request, Closure $next)
    {
        $dispatcher = $this->app->make('Nova\Routing\Assets\DispatcherInterface');

        if (! is_null($response = $dispatcher->dispatch($request))) {
            return $response;
        }

        return $next($request);
    }
}
