<?php

namespace Nova\Foundation\Http\Middleware;

use Nova\Foundation\Application;
use Nova\Http\Response;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Closure;


class CheckForMaintenanceMode
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
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        if ($this->app->isDownForMaintenance()) {
            $response = $this->callMaintenanceFilter();

            if (is_null($response)) {
                throw new HttpException(503);
            }

            // The maintenance Event returned something.
            else if (! $response instanceof SymfonyResponse) {
                $response = new Response($response);
            }

            return $response->prepare($request);
        }

        return $next($request);
    }

    protected function callMaintenanceFilter()
    {
        $events = $this->app['events'];

        return $events->until('nova.app.down');
    }
}
