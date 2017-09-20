<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Http\Request;
use Nova\Routing\Route;
use Nova\Routing\Router;
use Nova\Support\Str;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;


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
    * An array of all the know controller instances.
    *
    * @var \Nova\Routing\Controller
    */
    protected $controllers;

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
        'Domain', 'Method', 'URI', 'Name', 'Action', 'Before Filters', 'After Filters'
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
    public function fire()
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
        $results = array();

        foreach($this->routes as $route) {
            $results[] = $this->getRouteInformation($route);
        }

        return array_filter($results);
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
        $uri = implode('|', $route->methods()).' '.$route->uri();

        return $this->filterRoute(array(
            'host'   => $route->domain(),
            'method' => implode('|', $route->methods()),
            'uri'    => $route->uri(),
            'name'   => $route->getName(),
            'action' => $route->getActionName(),
            'before' => $this->getBeforeFilters($route),
            'after'  => $this->getAfterFilters($route)
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
    * Get before filters
    *
    * @param  \Nova\Routing\Route  $route
    * @return string
    */
    protected function getBeforeFilters($route)
    {
        $filters = array_keys($route->beforeFilters());

        $action = $route->getActionName();

        if ($action !== 'Closure') {
            list($controller, $method) = explode('@', $action);

            $filters = array_merge($filters, $this->parseControllerFilters(
                $instance = $this->getController($controller), $method, $instance->getBeforeFilters()
            ));
        }

        return implode(', ', array_unique($filters));
    }

    /**
    * Get after filters
    *
    * @param  Route  $route
    * @return string
    */
    protected function getAfterFilters($route)
    {
        $filters = array_keys($route->afterFilters());

        $action = $route->getActionName();

        if ($action !== 'Closure') {
            list($controller, $method) = explode('@', $action);

            $filters = array_merge($filters, $this->parseControllerFilters(
                $instance = $this->getController($controller), $method, $instance->getAfterFilters()
            ));
        }

        return implode(', ', array_unique($filters));
    }

    /**
    * Get controller instance
    *
    * @param  string  $controller
    * @return \Nova\Routing\Controller
    */
    protected function getController($controller)
    {
        if (isset($this->controllers[$controller])) {
            return $this->controllers[$controller];
        }

        return $this->controllers[$controller] = $this->container->make($controller);
    }

    /**
     * Get the route filters for the given controller instance and method.
     *
     * @param  \Nova\Routing\Controller  $controller
     * @param  string  $method
     * @param  array  $filters
     * @return array
     */
    protected function parseControllerFilters($controller, $method, array $filters)
    {
        $results = array();

        foreach ($filters as $filter) {
            if (static::methodExcludedByFilter($method, $filter)) {
                continue;
            }

            $result = $filter['filter'];

            if (! empty($filter['parameters'])) {
                $result .= ':' .implode(',', $filter['parameters']);
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Determine if the given options exclude a particular method.
     *
     * @param  string  $method
     * @param  array  $filter
     * @return bool
     */
    protected static function methodExcludedByFilter($method, array $filter)
    {
        $options = $filter['options'];

        return ((! empty($options['only']) && ! in_array($method, (array) $options['only'])) ||
            (! empty($options['except']) && in_array($method, (array) $options['except'])));
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
        } else {
            return $route;
        }
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
