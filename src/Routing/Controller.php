<?php

namespace Nova\Routing;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use BadMethodCallException;


abstract class Controller
{
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
     * Execute an action on the controller.
     *
     * @param string  $method
     * @param array   $params
     * @return mixed
     */
    public function callAction($method, $parameters)
    {
        return call_user_func_array(array($this, $method), $parameters);
    }

    /**
     * Register a "before" filter on the controller.
     *
     * @param  string  $filter
     * @param  array  $options
     * @return void
     */
    public function beforeFilter($filter, array $options = array())
    {
        $this->beforeFilters[$filter] = $options;
    }

    /**
     * Register an "after" filter on the controller.
     *
     * @param  string  $filter
     * @param  array  $options
     * @return void
     */
    public function afterFilter($filter, array $options = array())
    {
        $this->afterFilters[$filter] = $options;
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
     * Handle calls to missing methods on the Controller.
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
     * Handle calls to missing methods on the Controller.
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
