<?php

namespace Nova\View;

use Nova\Container\Container;
use Nova\Events\Dispatcher;
use Nova\Support\Contracts\ArrayableInterface as Arrayable;
use Nova\Support\Facades\Config;
use Nova\View\Engines\EngineResolver;
use Nova\View\View;
use Nova\View\ViewFinderInterface;

use Closure;


class Factory
{
    /**
     * The Engines Resolver instance.
     *
     * @var \Nova\View\Engines\EngineResolver
     */
    protected $engines;

    /**
     * The view finder implementation.
     *
     * @var \Nova\View\ViewFinderInterface
     */
    protected $finder;

    /**
     * The event dispatcher instance.
     *
     * @var \Nova\Events\Dispatcher
     */
    protected $events;

    /**
     * The IoC container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * @var array Array of shared data
     */
    protected $shared = array();

    /**
     * The extension to Engine bindings.
     *
     * @var array
     */
    protected $extensions = array('tpl.php' => 'template', 'php' => 'php');

    /**
     * The view composer events.
     *
     * @var array
     */
    protected $composers = array();


    /**
     * Create new View Factory instance.
     *
     * @param  \Nova\View\Engines\EngineResolver  $engines
     * @param  \Nova\View\ViewFinderInterface  $finder
     * @param  \Nova\Events\Dispatcher  $events
     * @return void
     */
    function __construct(EngineResolver $engines, ViewFinderInterface $finder, Dispatcher $events)
    {
        $this->finder  = $finder;
        $this->events  = $events;
        $this->engines = $engines;

        //
        $this->share('__env', $this);
    }

    /**
     * Create a View instance
     *
     * @param string $path
     * @param array $data
     * @param string|null $module
     * @return \Nova\View\View
     */
    public function make($view, array $data = array(), $module = null)
    {
        // Get the View file path.
        $path = $this->find($view, $module);

        // Parse the View data.
        $data = $this->parseData($data);

        $this->callCreator($view = new View($this, $this->getEngineFromPath($path), $view, $path, $data));

        return $view;
    }

    /**
     * Create a View instance and return its rendered content.
     *
     * @return string
     */
    public function fetch($view, $data = array(), $module = null, Closure $callback = null)
    {
        $instance = $this->make($view, $data, $module);

        return $instance->render($callback);
    }

    /**
     * Parse the given data into a raw array.
     *
     * @param  mixed  $data
     * @return array
     */
    protected function parseData($data)
    {
        return ($data instanceof Arrayable) ? $data->toArray() : $data;
    }

    /**
     * Register a view creator event.
     *
     * @param  array|string     $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($views, $callback)
    {
        $creators = array();

        foreach ((array) $views as $view) {
            $creators[] = $this->addViewEvent($view, $callback, 'creating: ');
        }

        return $creators;
    }

    /**
     * Register multiple view composers via an array.
     *
     * @param  array  $composers
     * @return array
     */
    public function composers(array $composers)
    {
        $registered = array();

        foreach ($composers as $callback => $views) {
            $registered = array_merge($registered, $this->composer($views, $callback));
        }

        return $registered;
    }

    /**
     * Register a view composer event.
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @param  int|null  $priority
     * @return array
     */
    public function composer($views, $callback, $priority = null)
    {
        $composers = array();

        foreach ((array) $views as $view) {
            $composers[] = $this->addViewEvent($view, $callback, 'composing: ', $priority);
        }

        return $composers;
    }

    /**
     * Add an event for a given view.
     *
     * @param  string  $view
     * @param  \Closure|string  $callback
     * @param  string  $prefix
     * @param  int|null  $priority
     * @return \Closure
     */
    protected function addViewEvent($view, $callback, $prefix = 'composing: ', $priority = null)
    {
        if ($callback instanceof Closure) {
            $this->addEventListener($prefix.$view, $callback, $priority);

            return $callback;
        } else if (is_string($callback)) {
            return $this->addClassEvent($view, $callback, $prefix, $priority);
        }
    }

    /**
     * Register a class based view composer.
     *
     * @param  string    $view
     * @param  string    $class
     * @param  string    $prefix
     * @param  int|null  $priority
     * @return \Closure
     */
    protected function addClassEvent($view, $class, $prefix, $priority = null)
    {
        $name = $prefix.$view;

        // When registering a class based view "composer", we will simply resolve the
        // classes from the application IoC container then call the compose method
        // on the instance. This allows for convenient, testable view composers.
        $callback = $this->buildClassEventCallback($class, $prefix);

        $this->addEventListener($name, $callback, $priority);

        return $callback;
    }

    /**
     * Add a listener to the event dispatcher.
     *
     * @param  string   $name
     * @param  \Closure $callback
     * @param  int      $priority
     * @return void
     */
    protected function addEventListener($name, $callback, $priority = null)
    {
        if (is_null($priority)) {
            $this->events->listen($name, $callback);
        } else {
            $this->events->listen($name, $callback, $priority);
        }
    }

    /**
     * Build a class based container callback Closure.
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return \Closure
     */
    protected function buildClassEventCallback($class, $prefix)
    {
        $container = $this->container;

        list($class, $method) = $this->parseClassEvent($class, $prefix);

        // Once we have the class and method name, we can build the Closure to resolve
        // the instance out of the IoC container and call the method on it with the
        // given arguments that are passed to the Closure as the composer's data.
        return function() use ($class, $method, $container)
        {
            $callable = array($container->make($class), $method);

            return call_user_func_array($callable, func_get_args());
        };
    }

    /**
     * Parse a class based composer name.
     *
     * @param  string  $class
     * @param  string  $prefix
     * @return array
     */
    protected function parseClassEvent($class, $prefix)
    {
        if (str_contains($class, '@')) {
            return explode('@', $class);
        }

        $method = str_contains($prefix, 'composing') ? 'compose' : 'create';

        return array($class, $method);
    }

    /**
     * Call the composer for a given view.
     *
     * @param  \Nova\View\View  $view
     * @return void
     */
    public function callComposer(View $view)
    {
        $this->events->fire('composing: ' .$view->getName(), array($view));
    }

    /**
     * Call the creator for a given view.
     *
     * @param  \Nova\View\View  $view
     * @return void
     */
    public function callCreator(View $view)
    {
        $this->events->fire('creating: ' .$view->getName(), array($view));
    }

    /**
     * Add a piece of shared data to the Factory.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function share($key, $value = null)
    {
        if (! is_array($key)) return $this->shared[$key] = $value;

        foreach ($key as $innerKey => $innerValue) {
            $this->share($innerKey, $innerValue);
        }
    }

    /**
     * Get an item from the shared data.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function shared($key, $default = null)
    {
        return array_get($this->shared, $key, $default);
    }

    /**
     * Get all of the shared data for the Factory.
     *
     * @return array
     */
    public function getShared()
    {
        return $this->shared;
    }

    /**
     * Check if the view file exists.
     *
     * @param    string     $view
     * @return    bool
     */
    public function exists($view, $module = null)
    {
        try {
            $this->find($view, $module);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Get the appropriate View Engine for the given path.
     *
     * @param  string  $path
     * @return \Nova\View\Engines\EngineInterface
     */
    public function getEngineFromPath($path)
    {
        $extension = $this->getExtension($path);

        $engine = $this->extensions[$extension];

        return $this->engines->resolve($engine);
    }

    /**
     * Get the extension used by the view file.
     *
     * @param  string  $path
     * @return string
     */
    protected function getExtension($path)
    {
        $extensions = array_keys($this->extensions);

        return array_first($extensions, function($key, $value) use ($path)
        {
            return ends_with($path, $value);
        });
    }

    /**
     * Register a valid view extension and its engine.
     *
     * @param  string   $extension
     * @param  string   $engine
     * @param  Closure  $resolver
     * @return void
     */
    public function addExtension($extension, $engine, $resolver = null)
    {
        $this->finder->addExtension($extension);

        if (isset($resolver)) {
            $this->engines->register($engine, $resolver);
        }

        unset($this->extensions[$extension]);

        $this->extensions = array_merge(array($extension => $engine), $this->extensions);
    }

    /**
     * Get the extension to engine bindings.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Get the engine resolver instance.
     *
     * @return \Nova\View\Engines\EngineResolver
     */
    public function getEngineResolver()
    {
        return $this->engines;
    }

    /**
     * Get the View Finder instance.
     *
     * @return \Nova\View\ViewFinderInterface
     */
    public function getFinder()
    {
        return $this->finder;
    }

    /**
     * Set the View Finder instance.
     *
     * @return void
     */
    public function setFinder(ViewFinderInterface $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Nova\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Nova\Events\Dispatcher
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the IoC container instance.
     *
     * @return \Nova\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Find the view file.
     *
     * @param    string      $view
     * @param    string|null $module
     * @return    string
     */
    protected function find($view, $module = null)
    {
        if (! is_null($module)) {
            $modulesPath = Config::get('modules.path', APPPATH .'Modules');

            $path = str_replace('/', DS, $modulesPath ."/$module/Views/$view");
        } else {
            $path = APPPATH .str_replace('/', DS, "Views/$view");
        }

        // Try to find the View file.
        $filePath = $this->finder->find($path);

        if (! is_null($filePath)) return $filePath;

        throw new \InvalidArgumentException("Unable to load the view '" .$view ."' on domain '" .($module ?: 'App')."'.", 1);
    }
}
