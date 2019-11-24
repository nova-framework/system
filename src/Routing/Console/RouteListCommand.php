<?php

namespace Nova\Routing\Console;

use Nova\Http\Request;
use Nova\Routing\ControllerDispatcher;
use Nova\Routing\Route;
use Nova\Routing\Router;
use Nova\Console\Command;
use Nova\Support\Arr;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;

use Closure;


class RouteListCommand extends Command
{
    /**
    * The console command name.
    *
    * @var string
    */
    protected $name = 'routes';

    /**
    * The console command description.
    *
    * @var string
    */
    protected $description = 'List all registered routes';

    /**
    * The router instance.
    *
    * @var \Nova\Routing\Router
    */
    protected $router;

    /**
    * An array of all the registered routes.
    *
    * @var \Nova\Routing\RouteCollection
    */
    protected $routes;

    /**
    * The table helper set.
    *
    * @var \Symfony\Component\Console\Helper\TableHelper
    */
    protected $table;

    /**
    * The table headers for the command.
    *
    * @var array
    */
    protected $headers = array(
        'Domain', 'Method', 'URI', 'Name', 'Action', 'Middleware'
    );

    /**
    * Create a new route command instance.
    *
    * @param  \Nova\Routing\Router  $router
    * @return void
    */
    public function __construct(Router $router)
    {
        parent::__construct();

        $this->router = $router;

        $this->routes = $router->getRoutes();
    }

    /**
    * Execute the console command.
    *
    * @return void
    */
    public function handle()
    {
        $this->table = new Table($this->output);

        if (count($this->routes) == 0) {
            return $this->error("Your application doesn't have any routes.");
        }

        $this->displayRoutes($this->getRoutes());
    }

    /**
    * Compile the routes into a displayable format.
    *
    * @return array
    */
    protected function getRoutes()
    {
        $routes = $this->routes->getRoutes();

        //
        $fallbacks = array();

        foreach ($routes as $key => $route) {
            if (! $route->isFallback()) {
                continue;
            }

            $fallbacks[$key] = $route;

            unset($routes[$key]);
        }

        return array_map(function ($route)
        {
            return $this->getRouteInformation($route);

        }, array_merge($routes, $fallbacks));
    }

    /**
    * Get the route information for a given route.
    *
    * @param  string  $name
    * @param  \Nova\Routing\Route  $route
    * @return array
    */
    protected function getRouteInformation(Route $route)
    {
        $methods = implode('|', $route->methods());

        $action = str_replace('\\Controllers\\', '\\...\\', $route->getActionName());

        $middleware = implode(', ', $this->getMiddleware($route));

        return $this->filterRoute(array(
            'host'       => $route->domain(),
            'method'     => $methods,
            'uri'        => $route->uri(),
            'name'       => $route->getName(),
            'action'     => $action,
            'middleware' => $middleware
        ));
    }

    /**
    * Display the route information on the console.
    *
    * @param  array  $routes
    * @return void
    */
    protected function displayRoutes(array $routes)
    {
        $this->table->setHeaders($this->headers)->setRows($routes);

        $this->table->render($this->getOutput());
    }

    /**
     * Get before filters.
     *
     * @param  \Nova\Routing\Route  $route
     * @return string
     */
    protected function getMiddleware($route)
    {
        return array_map(function ($middleware)
        {
            if ($middleware instanceof Closure) {
                return 'Closure';
            }

            return str_replace('\\Middleware\\', '\\...\\', $middleware);

        }, $route->middleware());
    }

    /**
    * Filter the route by URI and / or name.
    *
    * @param  array  $route
    * @return array|null
    */
    protected function filterRoute(array $route)
    {
        if (($this->option('name') && ! str_contains($route['name'], $this->option('name'))) ||
            $this->option('path') && ! str_contains($route['uri'], $this->option('path'))) {
            return null;
        }

        return $route;
    }

    /**
    * Get the console command options.
    *
    * @return array
    */
    protected function getOptions()
    {
        return array(
            array('name', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by name.'),
            array('path', null, InputOption::VALUE_OPTIONAL, 'Filter the routes by path.'),
        );
    }

}
