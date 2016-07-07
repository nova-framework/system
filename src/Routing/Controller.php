<?php

namespace Nova\Routing;

use Nova\Http\Response;
use Nova\View\View;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use BadMethodCallException;
use Closure;


abstract class Controller
{
    /**
     * The Module name.
     *
     * @var string|null
     */
    private $module = null;

    /**
     * The Default View.
     *
     * @var string
     */
    private $defaultView;

    /**
     * The currently used Template.
     *
     * @var string
     */
    protected $template;

    /**
     * The currently used Layout.
     *
     * @var string
     */
    protected $layout;

    /**
     * The "before" filters registered on the controller.
     *
     * @var array
     */
    protected $beforeFilters = array();

    /**
     * The "after" filters registered on the controller.
     *
     * @var array
     */
    protected $afterFilters = array();

    /**
     * The route filterer implementation.
     *
     * @var \Nova\Routing\RouteFiltererInterface
     */
    protected static $filterer;


    /**
     * Register a "before" filter on the controller.
     *
     * @param  \Closure|string  $filter
     * @param  array  $options
     * @return void
     */
    public function beforeFilter($filter, array $options = array())
    {
        $this->beforeFilters[] = $this->parseFilter($filter, $options);
    }

    /**
     * Register an "after" filter on the controller.
     *
     * @param  \Closure|string  $filter
     * @param  array  $options
     * @return void
     */
    public function afterFilter($filter, array $options = array())
    {
        $this->afterFilters[] = $this->parseFilter($filter, $options);
    }

    /**
     * Parse the given filter and options.
     *
     * @param  \Closure|string  $filter
     * @param  array  $options
     * @return array
     */
    protected function parseFilter($filter, array $options)
    {
        $parameters = array();

        $original = $filter;

        if ($filter instanceof Closure) {
            $filter = $this->registerClosureFilter($filter);
        } else if ($this->isInstanceFilter($filter)) {
            $filter = $this->registerInstanceFilter($filter);
        } else {
            list($filter, $parameters) = Route::parseFilter($filter);
        }

        return compact('original', 'filter', 'parameters', 'options');
    }

    /**
     * Register an anonymous controller filter Closure.
     *
     * @param  \Closure  $filter
     * @return string
     */
    protected function registerClosureFilter(Closure $filter)
    {
        $this->getFilterer()->filter($name = spl_object_hash($filter), $filter);

        return $name;
    }

    /**
     * Register a controller instance method as a filter.
     *
     * @param  string  $filter
     * @return string
     */
    protected function registerInstanceFilter($filter)
    {
        $this->getFilterer()->filter($filter, array($this, substr($filter, 1)));

        return $filter;
    }

    /**
     * Determine if a filter is a local method on the controller.
     *
     * @param  mixed  $filter
     * @return boolean
     *
     * @throws \InvalidArgumentException
     */
    protected function isInstanceFilter($filter)
    {
        if (is_string($filter) && starts_with($filter, '@'))
        {
            if (method_exists($this, substr($filter, 1))) return true;

            throw new \InvalidArgumentException("Filter method [$filter] does not exist.");
        }

        return false;
    }

    /**
     * Remove the given before filter.
     *
     * @param  string  $filter
     * @return void
     */
    public function forgetBeforeFilter($filter)
    {
        $this->beforeFilters = $this->removeFilter($filter, $this->getBeforeFilters());
    }

    /**
     * Remove the given after filter.
     *
     * @param  string  $filter
     * @return void
     */
    public function forgetAfterFilter($filter)
    {
        $this->afterFilters = $this->removeFilter($filter, $this->getAfterFilters());
    }

    /**
     * Remove the given controller filter from the provided filter array.
     *
     * @param  string  $removing
     * @param  array  $current
     * @return array
     */
    protected function removeFilter($removing, $current)
    {
        return array_filter($current, function($filter) use ($removing)
        {
            return $filter['original'] != $removing;
        });
    }

    /**
     * Get the registered "before" filters.
     *
     * @return array
     */
    public function getBeforeFilters()
    {
        return $this->beforeFilters;
    }

    /**
     * Get the registered "after" filters.
     *
     * @return array
     */
    public function getAfterFilters()
    {
        return $this->afterFilters;
    }

    /**
     * Get the route filterer implementation.
     *
     * @return \Nova\Routing\RouteFiltererInterface
     */
    public static function getFilterer()
    {
        return static::$filterer;
    }

    /**
     * Set the route filterer implementation.
     *
     * @param  \Nova\Routing\RouteFiltererInterface  $filterer
     * @return void
     */
    public static function setFilterer(RouteFiltererInterface $filterer)
    {
        static::$filterer = $filterer;
    }

    /**
     * Create the layout used by the controller.
     *
     * @return void
     */
    protected function setupLayout() {}

    /**
     * Execute an action on the controller.
     *
     * @param string  $method
     * @param array   $params
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        $this->setupLayout();

        // Execute the requested Method with the given arguments.
        $response = call_user_func_array(array($this, $method), $parameters);

        // If the response is returned from the controller action is a SymfonyResponse
        // instance, we will assume we want to just return the response.
        if ($response instanceof SymfonyResponse) {
            return $response;
        }

        // If the response is returned from the controller action is a View instance
        // and it is not marked as Template, we will assume we want to render it on the
        // default templated environment, setup via the current controller properties.
        else if ($response instanceof View) {
            if (is_string($this->layout) && ! $response->isTemplate()) {
                $response = app('template')
                    ->make($this->layout, $this->template)
                    ->with('content', $response->fetch());
            }
        }

        // If no response is returned from the controller action and a layout is being
        // used we will assume we want to just return the Layout view as any nested
        // Views were probably bound on this view during this Controller actions.
        else if (is_null($response)) {
            if ($this->layout instanceof View) {
                $response = $this->layout;
            }
        }

        // Create a proper Response and return it.
        return $response;
    }

    /**
     * @param  string $title
     *
     * @return \Nova\Routing\Controller
     */
    protected function title($title)
    {
        app('view')->share('title', $title);
    }

    /**
     * Return a default View instance.
     *
     * @return \Nova\View\View
     */
    protected function getView(array $data = array())
    {
        if(! isset($this->defaultView)) {
            list(, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $this->setupDefaultView($caller['function']);
        }

        return app('view')->make($this->defaultView, $data, $this->module);
    }

    /**
     * @return void
     */
    private function setupDefaultView($method)
    {
        // Prepare the View Path using the Controller's full Name including its namespace.
        $classPath = str_replace('\\', '/', static::class);

        if (preg_match('#^App/Controllers/(.*)$#i', $classPath, $matches)) {
            // The View is in default App sub-directory.
            $this->defaultView = $matches[1] .DS .ucfirst($method);
        } else if (preg_match('#^App/Modules/(.+)/Controllers/(.*)$#i', $classPath, $matches)) {
             // The View is in a Module sub-directory.
            $this->module = $matches[1];

            $this->defaultView = $matches[2] .DS .ucfirst($method);
        } else {
            throw new BadMethodCallException('Invalid Controller: ' .static::class);
        }
    }

    /**
     * @return string|null
     */
    protected function getModule()
    {
        return $this->module;
    }

    /**
     * @return mixed
     */
    protected function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return mixed
     */
    protected function getLayout()
    {
        return $this->layout;
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function missingMethod($parameters = array())
    {
        throw new NotFoundHttpException("Controller method not found.");
    }

    /**
     * Handle calls to missing methods on the controller.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        throw new \BadMethodCallException("Method [$method] does not exist.");
    }

}
