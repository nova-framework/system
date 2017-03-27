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
    private $method;

    /**
     * The currently call parameters.
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * The middleware registered on the controller.
     *
     * @var array
     */
    protected $middleware = array();


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
     * Method executed before any action.
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

        $this->parameters = $parameters;
        
        // Execute the Before method.
        $response = $this->before();

        if (! is_null($response)) {
            return $this->processResponse($response);
        }

        // Execute the requested Method with the given arguments.
        $response = call_user_func_array(array($this, $method), $parameters);

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
     * Returns the currently call parameters.
     *
     * @return string|null
     */
    public function getParameters()
    {
        return $this->parameters;
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
