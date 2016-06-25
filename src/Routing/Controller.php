<?php

namespace Nova\Routing;

use Nova\View\View;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Response;
use Template;
use View as ViewFacade;

use BadMethodCallException;
use Closure;


abstract class Controller
{
    /**
     * The requested Method by Router.
     *
     * @var string|null
     */
    private $method = null;

    /**
     * The parameters given by Router.
     *
     * @var array
     */
    private $parameters = array();

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
    protected $layout = 'default';

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
     * On the initial run, create an instance of the config class and the view class.
     */
    public function __construct()
    {
        // Adjust to the default Template, if it is not defined.
        if(! isset($this->template)) {
            $config = app('config');

            $this->template = $config['app.template'];
        }
    }

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
        if (is_string($filter) && starts_with($filter, '@')) {
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
     * Execute an action on the controller.
     *
     * @param string  $method
     * @param array   $params
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        $this->method = $method;
        $this->parameters = $parameters;

        // Before the Action execution stage.
        $response = $this->before();

        // Process the stage result.
        if (! $response instanceof SymfonyResponse) {
            // Execute the requested Method with the given arguments.
            $response = call_user_func_array(array($this, $method), $parameters);
        }

        // Create a proper Response and return it.
        return $this->prepareResponse($response);
    }

    /**
     * Create from the given result a Response instance and send it.
     *
     * @param mixed  $result
     * @return bool
     */
    private function prepareResponse($response)
    {
        if ($response instanceof SymfonyResponse) {
            return $response;
        }

        if (($this->layout !== false) && ($response instanceof View) && ! $response->isTemplate()) {
            $response = Template::make($this->layout, $this->template)->with('content', $response);
        }

        // Create a Response instance and return it.
        return Response::make($response);
    }

    /**
     * Method automatically invoked before the requested Action.
     *
     * @return mixed|void
     */
    protected function before()
    {
    }

    /**
     * @return void
     */
    private function setupDefaultView()
    {
        if (isset($this->defaultView)) {
            return;
        }

        // Prepare the View Path using the Controller's full Name including its namespace.
        $path = str_replace('\\', '/', ltrim(static::class, '\\'));

        // First, check on the App path.
        if (preg_match('#^App/Controllers/(.*)$#i', $path, $matches)) {
            $this->defaultView = $matches[1] .DS .ucfirst($this->method);
            // Secondly, check on the Modules path.
        } else if (preg_match('#^App/Modules/(.+)/Controllers/(.*)$#i', $path, $matches)) {
            $this->module = $matches[1];

            // The View is in Module sub-directories.
            $this->defaultView = $matches[2] .DS .ucfirst($this->method);
        } else {
            throw new BadMethodCallException('Invalid Controller: ' .static::class);
        }
    }

    /**
     * @param  string $title
     *
     * @return \Nova\Routing\Controller
     */
    protected function title($title)
    {
        ViewFacade::share('title', $title);
    }

    /**
     * Return a default View instance.
     *
     * @return \Nova\View\View
     */
    protected function getView(array $data = array())
    {
        $this->setupDefaultView();

        return ViewFacade::make($this->defaultView, $data, $this->module);
    }

    /**
     * @return string
     */
    protected function getViewName()
    {
        $this->setupDefaultView();

        return $this->defaultView;
    }

    /**
     * @return string|null
     */
    protected function getModule()
    {
        $this->setupDefaultView();

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
     * @return mixed
     */
    protected function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    protected function getParameters()
    {
        return $this->parameters;
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
        throw new BadMethodCallException("Method [$method] does not exist.");
    }

}
