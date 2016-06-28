<?php

namespace Nova\Module;

use Nova\Foundation\AliasLoader;
use Nova\Foundation\Application;
use Nova\Support\ServiceProvider;


class Module extends ServiceProvider
{
    /**
     * The Name of the Module
     * @var string
     */
    protected $name;

    /**
     * The Path to Module directory
     *
     * @var string
     */
    protected $path;

    /**
     * The Path to module definition JSON file
     *
     * @var string
     */
    protected $definitionPath;

    /**
     * The Module definition
     *
     * @var array
     */
    protected $definition;

    /**
     * Is the module enabled
     * @var boolean
     */
    protected $enabled = true;

    /**
     * The order to register the Module
     *
     * @var integer
     */
    public $order = 0;

    /**
     * The Application instance
     *
     * @var Nova\Foundation\Application
     */
    protected $app;

    /**
     * The Path for Module group
     * @var string
     */
    public $group;

    /**
     * Initialize a Module instance
     *
     * @param Application $app
     */
    public function __construct($name, $path = null, $definition = null, Application $app, $group = null)
    {
        $this->name  = $name;
        $this->app   = $app;
        $this->path  = $path;
        $this->group = $group;

        // Get definition
        if (! is_null($path) && is_null($definition)) {
            $this->definitionPath = $path .DS .'module.json';
        } else if (is_array($definition)) {
            $this->definition = $definition;
        }

        // Try to get the definition
        $this->readDefinition();
    }

    /**
     * Read the Module definition
     * @return array
     */
    public function readDefinition()
    {
        // Read mode from configuration
        $mode = $this->app['config']['modules.mode'];

        if (($mode == 'auto') || (($mode == 'manifest') && ! $this->app['modules']->manifest())) {
            if ($this->definitionPath) {
                $this->definition = @json_decode($this->app['files']->get($this->definitionPath), true);

                if (! iset($this->definition) || (isset($this->definition['enabled']) && ($this->definition['enabled'] === false))) {
                    $this->enabled = false;
                }
            } else {
                $this->enabled = false;
            }
        } else {
            if (isset($this->definition['enabled']) && ($this->definition['enabled'] === false)) {
                $this->enabled = false;
            }
        }

        // Add the name to definition.
        if (! isset($this->definition['name'])) {
            $this->definition['name'] = $this->name;
        }

        // Assign the order number.
        if (! isset($this->definition['order'])) {
            $this->definition['order'] = $this->order = 0;
        } else {
            $this->definition['order'] = $this->order = (int) $this->definition['order'];
        }

        // Add group to definition
        $this->definition['group'] = $this->group;

        return $this->definition;
    }

    /**
     * Register the Module if it is enabled
     *
     * @return boolean
     */
    public function register()
    {
        if (! $this->enabled) {
            return;
        }

        // Register service provider
        $this->registerProviders();

        // Get files for inclusion
        $moduleInclude = (array) array_get($this->definition, 'include');

        $globalInclude = $this->app['config']->get('modules.include');

        $includes = array_merge($globalInclude, $moduleInclude);

        // Include all of them if they exist
        foreach ($includes as $file) {
            $path = $this->path($file);

            if ($this->app['files']->exists($path)) require $path;
        }

        // Register alias(es) into Forge
        $aliases = $this->get('alias');

        if(! is_null($aliases)) {
            if(! is_array($aliases)) $aliases = array($aliases);

            foreach($aliases as $alias => $facade) {
                AliasLoader::getInstance()->alias($alias, $facade);
            }
        }

        // Register command(s) into Forge
        $commands = $this->get('command');

        if(! is_null($commands)) {
            if(! is_array($commands)) $commands = array($commands);

            $this->commands($commands);
        }
    }

    /**
     * Register the Service Provider for Module
     * @return void
     */
    public function registerProviders()
    {
        $providers = $this->get('provider');

        if (is_null($providers)) {
            return;
        } else if (is_array($providers)) {
            foreach ($providers as $provider) {
                $this->app->register($instance = new $provider($this->app));
            }
        } else {
            $this->app->register($instance = new $providers($this->app));
        }
    }

    /**
     * Run the seeder if it exists
     * @return void
     */
    public function seed()
    {
        $className = $this->get('seeder');

        if (! class_exists($className)) {
            return;
        }

        $seeder = new $className();

        $seeder->run();
    }

    /**
     * Return name of module
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Module path
     * @param  string $path
     * @return string
     */
    public function path($path = null)
    {
        if (! is_null($path)) {
            return $this->path . DS . ltrim($path, DS);
        }

        return $this->path;
    }

    /**
     * Check if module is enabled
     * @return boolean
     */
    public function enabled()
    {
        return (bool) $this->enabled;
    }

    /**
     * Check if a definition exists
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        $value = $this->get($key);

        return ! is_null($value);
    }

    /**
     * Get definition value
     * @param  string $key
     * @return mixed
     */
    public function get($key = null)
    {
        if (! isset($this->definition['enabled'])) {
            $this->definition['enabled'] = $this->enabled;
        }

        if (! is_null($key)) {
            return isset($this->definition[$key]) ? $this->definition[$key] : null;
        }

        return $this->definition;
    }

}
