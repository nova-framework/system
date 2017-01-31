<?php

namespace Nova\Widget;

use Nova\Foundation\Application;
use Nova\Support\Str;
use Nova\Widget\Exceptions\InvalidWidgetException;


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
     * Determine the full namespace for the given class.
     *
     * @param  string  $className
     * @return string
     */
    protected function determineNamespace($className)
    {
        if (count($this->namespaces) > 0) {
            foreach ($this->namespaces as $namespace) {
                if (class_exists($namespace .'\\' .$className)) {
                    return $namespace;
                }
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

    /**
     * Magic method to call widget instances.
     *
     * @param  string  $signature
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($signature, $parameters)
    {
        $parameters = $this->flattenParameters($parameters);

        $className = Str::studly($signature);

        $namespace = $this->determineNamespace($className);

        $widgetClass = $namespace .'\\' .$className;

        $widget = $this->app->make($widgetClass);

        if (! $widget instanceof Widget) {
            throw new InvalidWidgetException();
        }

        $widget->registerParameters($parameters);

        return $widget->handle();
    }
}
