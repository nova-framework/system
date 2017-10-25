<?php

namespace Nova\View\Middleware;

use Nova\Support\ViewErrorBag;
use Nova\View\Factory as ViewFactory;

use Closure;


class ShareErrorsFromSession
{
    /**
     * The view factory implementation.
     *
     * @var \Nova\Contracts\View\Factory
     */
    protected $view;

    /**
     * Create a new error binder instance.
     *
     * @param  \Nova\View\Factory  $view
     * @return void
     */
    public function __construct(ViewFactory $view)
    {
        $this->view = $view;
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
        $this->view->share(
            'errors', $request->session()->get('errors', new ViewErrorBag())
        );

        return $next($request);
    }
}
