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
        $route = $this->createSymfonyRoute();

        return $route->compile();
    }

    /**
     * Create a Symfony Route from the inner Route instance.
     *
     * @return \Symfony\Component\Routing\CompiledRoute
     */
    protected function createSymfonyRoute()
    {
        $route = $this->getRoute();

        if (empty($domain = $route->domain())) {
            $domain = '';
        }

        $path = preg_replace('/\{(\w+?)\?\}/', '{$1}', $uri = $route->uri());

        $optionals = $this->extractOptionalParameters($uri);

        return new SymfonyRoute($path, $optionals, $route->patterns(), array(), $domain);
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

        if (! isset($matches[1])) {
            return array();
        }

        return array_fill_keys($matches[1], null);
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
