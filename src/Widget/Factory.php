<?php

namespace Nova\Widget;

use Nova\Foundation\Application;
use Nova\Support\Str;
use Nova\Widget\InvalidWidgetException;


class Factory
{
    /**
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $namespaces = array();


    /**
     * Create a new factory instance.
     *
     * @param  Nova\Foundation\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register a new namespace location where widgets may be found.
     *
     * @param  string  $namespace
     */
    public function register($namespace)
    {
        if (! array_key_exists($namespace, $this->namespaces)) {
            $this->namespaces[] = $namespace;
        }
    }

    /**
     * Create a new Widget instance.
     *
     * @param  string  $signature
     *
     * @return \Nova\Widget\Widget
     */
    public function make($signature)
    {
        $className = Str::studly($signature);

        $namespace = $this->determineNamespace($className);

        $widgetClass = $namespace .'\\' .$className;

        return $this->app->make($widgetClass);
    }

    /**
     * Handle a Widget instance.
     *
     * @param  \Nova\Widget\Widget $widget
     * @param array $parameters
     *
     * @return mixed
     * @throws \Nova\Widget\InvalidWidgetException
     */
    public function handle($widget, array $parameters = array())
    {
        $parameters = $this->flattenParameters($parameters);

        if (! $widget instanceof Widget) {
            throw new InvalidWidgetException();
        }

        $widget->registerParameters($parameters);

        return $widget->handle();
    }

    /**
     * Determine the full namespace for the given class.
     *
     * @param  string  $className
     * @return string
     */
    protected function determineNamespace($className)
    {
        foreach ($this->namespaces as $namespace) {
            if (class_exists($namespace .'\\' .$className)) {
                return $namespace;
            }
        }

        return 'App\\Widgets';
    }

    /**
     * Flattens the given array.
     *
     * @param  array  $parameters
     * @return array
     */
    protected function flattenParameters(array $parameters)
    {
        $flattened = array();

        foreach($parameters as $parameter) {
            array_walk($parameter, function($value, $key) use (&$flattened)
            {
                $flattened[$key] = $value;
            });
        }

        return $flattened;
    }

    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Magic method to call widget instances.
     *
     * @param  string  $signature
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($signature, $parameters)
    {
        $widget = $this->make($signature);

        return $this->handle($widget, $parameters);
    }
}
