<?php

namespace Nova\Routing;

use Nova\Http\Response;
use Nova\Routing\Router;
use Nova\View\View;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use BadMethodCallException;
use Closure;


abstract class Controller
{
    /**
     * The currently called Method.
     *
     * @var mixed
     */
    private $method = null;

    /**
     * The currently used Layout.
     *
     * @var mixed
     */
    protected $layout;

    /**
     * The middleware registered on the controller.
     *
     * @var array
     */
    protected $middleware = array();

    /**
     * The router implementation.
     *
     * @var \Nova\Routing\Router
     */
    protected static $router;


    /**
     * Register middleware on the controller.
     *
     * @param  string  $middleware
     * @param  array   $options
     * @return void
     */
    public function middleware($middleware, array $options = array())
    {
        $this->middleware[$middleware] = $options;
    }

    /**
     * Get the middleware assigned to the controller.
     *
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }


    /**
     * Get the route implementation.
     *
     * @return \Nova\Routing\Router
     */
    public static function getRouter()
    {
        return static::$router;
    }

    /**
     * Set the route filterer implementation.
     *
     * @param  \Nova\Routing\Router  $router
     * @return void
     */
    public static function setRouter(Router $router)
    {
        static::$router = $router;
    }

    /**
     * Create the layout used by the controller.
     *
     * @return void
     */
    protected function before() {}

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

        // Setup the Layout.
        $this->before();

        // Execute the requested Method with the given arguments.
        $response = call_user_func_array(array($this, $method), $parameters);

        // If no response is returned from the controller action and a layout is being
        // used we will assume we want to just return the Layout view as any nested
        // Views were probably bound on this view during this Controller actions.
        if (is_null($response) && ($this->layout instanceof View)) {
            $response = $this->layout;
        }

        // Process the Response and return it.
        return $this->processResponse($response);
    }

    /**
     * Process the response given by the controller action.
     *
     * @param mixed $response
     *
     * @return mixed
     */
    protected function processResponse($response)
    {
        if (! $response instanceof SymfonyResponse) {
            $response = new Response($response);
        }

        return $response;
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
     * Returns the currently called Method.
     *
     * @return string|null
     */
    public function getMethod()
    {
        return $this->method;
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
