<?php

namespace Nova\Routing;

use Nova\Support\Str;

use ReflectionClass;
use ReflectionMethod;


class ControllerInspector
{
    /**
     * An array of HTTP verbs.
     *
     * @var array
     */
    protected $verbs = array('any', 'get', 'post', 'put', 'patch', 'delete', 'head', 'options');


    /**
     * Get the routable methods for a controller.
     *
     * @param  string  $controller
     * @param  string  $prefix
     * @return array
     */
    public function getRoutable($controller, $prefix)
    {
        $routable = array();

        $reflection = new ReflectionClass($controller);

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $name = $method->name;

            if (! $this->isRoutable($method, $controller)) {
                continue;
            }

            $routable[$name] = array();

            //
            $data = $this->getMethodData($method, $prefix);

            $routable[$name][] = $data;

            if ($data['plain'] == $prefix .'/index') {
                $routable[$name][] = $this->getIndexData($data, $prefix);
            }
        }

        return $routable;
    }

    /**
     * Determine if the given controller method is routable.
     *
     * @param  \ReflectionMethod  $method
     * @param  string  $controller
     * @return bool
     */
    public function isRoutable(ReflectionMethod $method, $controller)
    {
        if ($method->class != $controller) {
            return false;
        }

        return Str::startsWith($method->name, $this->verbs);
    }

    /**
     * Get the method data for a given method.
     *
     * @param  \ReflectionMethod  $method
     * @param  string  $prefix
     * @return array
     */
    public function getMethodData(ReflectionMethod $method, $prefix)
    {
        list ($verb, $plain) = $this->getMethodInfo($name, $prefix);

        $uri = $this->addUriWildcards($plain);

        return compact('verb', 'plain', 'uri');
    }

    /**
     * Get the routable data for an index method.
     *
     * @param  array   $data
     * @param  string  $prefix
     * @return array
     */
    protected function getIndexData($data, $prefix)
    {
        return array('verb' => $data['verb'], 'plain' => $prefix, 'uri' => $prefix);
    }

    /**
     * Determine the verb and URI from the given method name.
     *
     * @param  string  $name
     * @param  string  $prefix
     * @return string
     */
    public function getMethodInfo($name, $prefix)
    {
        $parts = explode('_', Str::snake($name));

        return array(
            head($parts), $prefix .'/' .implode('-', array_slice($parts, 1))
        );
    }

    /**
     * Add wildcards to the given URI.
     *
     * @param  string  $uri
     * @return string
     */
    public function addUriWildcards($uri)
    {
        return $uri .'/{one?}/{two?}/{three?}/{four?}/{five?}';
    }

}
