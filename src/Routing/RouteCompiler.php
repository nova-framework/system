<?php

namespace Nova\Routing;

use Nova\Routing\Route;

use Symfony\Component\Routing\Route as SymfonyRoute;


class RouteCompiler
{
    /**
     * The route instance.
     *
     * @var \Nova\Routing\Route
     */
    protected $route;

    /**
     * Create a new Route compiler instance.
     *
     * @param  \Nova\Routing\Route  $route
     * @return void
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * Compile the route.
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    public function compile()
    {
        $route = $this->getRoute();

        //
        $optionals = $this->extractOptionalParameters($route->uri());

        $uri = preg_replace('/\{(\w+?)\?\}/', '{$1}', $route->uri());

        return with(
            new SymfonyRoute($uri, $optionals, $route->wheres, array(), $route->domain() ?: '')

        )->compile();
    }

    /**
     * Get the optional parameters for the route.
     *
     * @param string $uri
     *
     * @return array
     */
    protected function extractOptionalParameters($uri)
    {
        preg_match_all('/\{(\w+?)\?\}/', $uri, $matches);

        return isset($matches[1]) ? array_fill_keys($matches[1], null) : array();
    }

    /**
     * Get the inner Route instance.
     *
     * @return \Nova\Routing\Route
     */
    public function getRoute()
    {
        return $this->route;
    }
}
