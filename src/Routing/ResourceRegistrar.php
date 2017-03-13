<?php

namespace Nova\Routing;

use Nova\Support\Str;


class ResourceRegistrar
{
    /**
     * The router instance.
     *
     * @var \Nova\Routing\Router
     */
    protected $router;

    /**
     * The default actions for a resourceful controller.
     *
     * @var array
     */
    protected $resourceDefaults = array('index', 'create', 'store', 'show', 'edit', 'update', 'destroy');


    /**
     * Create a new resource registrar instance.
     *
     * @param  \Nova\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function register($name, $controller, array $options = array())
    {
        if (Str::contains($name, '/')) {
            $this->prefixedResource($name, $controller, $options);

            return;
        }

        $base = $this->getResourceWildcard(last(explode('.', $name)));

        $defaults = $this->resourceDefaults;

        foreach ($this->getResourceMethods($defaults, $options) as $method) {
            $method = 'addResource' .ucfirst($method);

            $this->{$method}($name, $base, $controller, $options);
        }
    }

    /**
     * Build a set of prefixed resource routes.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    protected function prefixedResource($name, $controller, array $options)
    {
        list($name, $prefix) = $this->getResourcePrefix($name);

        $callback = function ($me) use ($name, $controller, $options) {
            $me->resource($name, $controller, $options);
        };

        return $this->router->group(compact('prefix'), $callback);
    }

    /**
     * Extract the resource and prefix from a resource name.
     *
     * @param  string  $name
     * @return array
     */
    protected function getResourcePrefix($name)
    {
        $segments = explode('/', $name);

        $prefix = implode('/', array_slice($segments, 0, -1));

        return array(end($segments), $prefix);
    }

    /**
     * Get the applicable resource methods.
     *
     * @param  array  $defaults
     * @param  array  $options
     * @return array
     */
    protected function getResourceMethods($defaults, $options)
    {
        if (isset($options['only'])) {
            return array_intersect($defaults, (array) $options['only']);
        } elseif (isset($options['except'])) {
            return array_diff($defaults, (array) $options['except']);
        }

        return $defaults;
    }

    /**
     * Get the base resource URI for a given resource.
     *
     * @param  string  $resource
     * @return string
     */
    public function getResourceUri($resource)
    {
        if (! Str::contains($resource, '.')) {
            return $resource;
        }

        $segments = explode('.', $resource);

        $uri = $this->getNestedResourceUri($segments);

        return str_replace('/{' .$this->getResourceWildcard(end($segments)) .'}', '', $uri);
    }

    /**
     * Get the URI for a nested resource segment array.
     *
     * @param  array   $segments
     * @return string
     */
    protected function getNestedResourceUri(array $segments)
    {
        return implode('/', array_map(function ($segment)
        {
            return $segment .'/{'.$this->getResourceWildcard($segment) .'}';

        }, $segments));
    }

    /**
     * Get the action array for a resource route.
     *
     * @param  string  $resource
     * @param  string  $controller
     * @param  string  $method
     * @param  array   $options
     * @return array
     */
    protected function getResourceAction($resource, $controller, $method, $options)
    {
        $name = $this->getResourceName($resource, $method, $options);

        return array('as' => $name, 'uses' => $controller .'@' .$method);
    }

    /**
     * Get the name for a given resource.
     *
     * @param  string  $resource
     * @param  string  $method
     * @param  array   $options
     * @return string
     */
    protected function getResourceName($resource, $method, $options)
    {
        if (isset($options['names'][$method])) {
            return $options['names'][$method];
        }

        $prefix = isset($options['as']) ? $options['as'].'.' : '';

        if (! $this->router->hasGroupStack()) {
            return $prefix .$resource .'.' .$method;
        }

        return $this->getGroupResourceName($prefix, $resource, $method);
    }

    /**
     * Get the resource name for a grouped resource.
     *
     * @param  string  $prefix
     * @param  string  $resource
     * @param  string  $method
     * @return string
     */
    protected function getGroupResourceName($prefix, $resource, $method)
    {
        $group = trim(str_replace('/', '.', $this->router->getLastGroupPrefix()), '.');

        if (empty($group)) {
            return trim("{$prefix}{$resource}.{$method}", '.');
        }

        return trim("{$prefix}{$group}.{$resource}.{$method}", '.');
    }

    /**
     * Format a resource wildcard for usage.
     *
     * @param  string  $value
     * @return string
     */
    public function getResourceWildcard($value)
    {
        return str_replace('-', '_', $value);
    }

    /**
     * Add the index method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Nova\Routing\Route
     */
    protected function addResourceIndex($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name);

        $action = $this->getResourceAction($name, $controller, 'index', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the create method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Nova\Routing\Route
     */
    protected function addResourceCreate($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name) .'/create';

        $action = $this->getResourceAction($name, $controller, 'create', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the store method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Nova\Routing\Route
     */
    protected function addResourceStore($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name);

        $action = $this->getResourceAction($name, $controller, 'store', $options);

        return $this->router->post($uri, $action);
    }

    /**
     * Add the show method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Nova\Routing\Route
     */
    protected function addResourceShow($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'show', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the edit method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Nova\Routing\Route
     */
    protected function addResourceEdit($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}/edit';

        $action = $this->getResourceAction($name, $controller, 'edit', $options);

        return $this->router->get($uri, $action);
    }

    /**
     * Add the update method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    protected function addResourceUpdate($name, $base, $controller, $options)
    {
        $this->addPutResourceUpdate($name, $base, $controller, $options);

        return $this->addPatchResourceUpdate($name, $base, $controller);
    }

    /**
     * Add the update method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Nova\Routing\Route
     */
    protected function addPutResourceUpdate($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'update', $options);

        return $this->router->put($uri, $action);
    }

    /**
     * Add the update method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @return void
     */
    protected function addPatchResourceUpdate($name, $base, $controller)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';

        $this->router->patch($uri, $controller.'@update');
    }

    /**
     * Add the destroy method for a resourceful route.
     *
     * @param  string  $name
     * @param  string  $base
     * @param  string  $controller
     * @param  array   $options
     * @return \Nova\Routing\Route
     */
    protected function addResourceDestroy($name, $base, $controller, $options)
    {
        $uri = $this->getResourceUri($name).'/{'.$base.'}';

        $action = $this->getResourceAction($name, $controller, 'destroy', $options);

        return $this->router->delete($uri, $action);
    }
}
