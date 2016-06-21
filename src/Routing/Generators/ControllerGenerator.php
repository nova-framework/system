<?php

namespace Nova\Routing\Generators;

use Nova\Filesystem\Filesystem;


class ControllerGenerator
{
    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The default resource controller methods.
     *
     * @var array
     */
    protected $defaults = array(
        'index',
        'create',
        'store',
        'show',
        'edit',
        'update',
        'destroy'
    );

    /**
     * Create a new controller generator instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Create a new resourceful controller file.
     *
     * @param  string  $controller
     * @param  string  $path
     * @param  array   $options
     * @return void
     */
    public function make($controller, $path, array $options = array())
    {
        $stub = $this->addMethods($this->getController($controller), $options);

        $this->writeFile($stub, $controller, $path);

        return false;
    }

    /**
     * Write the completed stub to disk.
     *
     * @param  string  $stub
     * @param  string  $controller
     * @param  string  $path
     * @return void
     */
    protected function writeFile($stub, $controller, $path)
    {
        if (str_contains($controller, '\\')) {
            $this->makeDirectory($controller, $path);
        }

        $controller = str_replace('\\', DS, $controller);

        if (! $this->files->exists($fullPath = $path .DS .$controller .'.php')) {
            return $this->files->put($fullPath, $stub);
        }
    }

    /**
     * Create the directory for the controller.
     *
     * @param  string  $controller
     * @param  string  $path
     * @return void
     */
    protected function makeDirectory($controller, $path)
    {
        $directory = $this->getDirectory($controller);

        if (! $this->files->isDirectory($full = $path .DS .$directory)) {
            $this->files->makeDirectory($full, 0777, true);
        }
    }

    /**
     * Get the directory the controller should live in.
     *
     * @param  string  $controller
     * @return string
     */
    protected function getDirectory($controller)
    {
        return implode(DS, array_slice(explode('\\', $controller), 0, -1));
    }

    /**
     * Get the controller class stub.
     *
     * @param  string  $controller
     * @return string
     */
    protected function getController($controller)
    {
        $stub = $this->files->get(__DIR__ .DS .'stubs' .DS .'controller.stub');

        $segments = explode('\\', $controller);

        $stub = $this->replaceNamespace($segments, $stub);

        return str_replace('{{class}}', last($segments), $stub);
    }

    /**
     * Replace the namespace on the controller.
     *
     * @param  array   $segments
     * @param  string  $stub
     * @return string
     */
    protected function replaceNamespace(array $segments, $stub)
    {
        if (count($segments) > 1) {
            $namespace = implode('\\', array_slice($segments, 0, -1));

            return str_replace('{{namespace}}', 'namespace '.$namespace.';', $stub);
        } else {
            return str_replace('{{namespace}}', 'namespace App\Controllers', $stub);
        }
    }

    /**
     * Add the method stubs to the controller.
     *
     * @param  string  $stub
     * @param  array   $options
     * @return string
     */
    protected function addMethods($stub, array $options)
    {
        $stubs = $this->getMethodStubs($options);

        $methods = implode(PHP_EOL, $stubs);

        return str_replace('{{methods}}', $methods, $stub);
    }

    /**
     * Get all of the method stubs for the given options.
     *
     * @param  array  $options
     * @return array
     */
    protected function getMethodStubs($options)
    {
        $stubs = array();

        foreach ($this->getMethods($options) as $method) {
            $stubs[] = $this->files->get(__DIR__ .DS .'stubs' .DS .$method .'.stub');
        }

        return $stubs;
    }

    /**
     * Get the applicable methods based on the options.
     *
     * @param  array  $options
     * @return array
     */
    protected function getMethods($options)
    {
        if (isset($options['only']) && count($options['only']) > 0) {
            return $options['only'];
        } else if (isset($options['except']) && count($options['except']) > 0) {
            return array_diff($this->defaults, $options['except']);
        }

        return $this->defaults;
    }

}
