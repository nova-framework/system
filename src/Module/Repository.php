<?php

namespace Nova\Module;

use Nova\Foundation\Application;
use Nova\Module\Collection;
use Nova\Module\Manifest;
use Nova\Module\Module;


class Repository
{
    /**
     * The Module Collection instance
     *
     * @var \Nova\Module\Collection
     */
    protected $modules;

    /**
     * The Modules Manifest instance
     *
     * @var \Nova\Module\Manifest
     */
    protected $manifest;

    protected $manifestPath;

    /**
     * The Application instance
     *
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * Initialize the finder
     * @param Application $app
     */
    public function __construct(Application $app, $manifestPath)
    {
        $this->app = $app;

        $this->modules = new Collection($app);

        $this->manifest = new Manifest($app);

        $this->manifestPath = $manifestPath;
    }

    /**
     * Start finder
     * @return void
     */
    public function start()
    {
        $mode = $this->app['config']['modules.mode'];

        $modules = $this->app['modules'];

        if ($mode == 'auto') {
            $modules->scan();
        } else if ($mode == 'manifest') {
            $manifest = $this->manifest->toArray();

            if (! is_null($manifest)) {
                $modules->manual($manifest);
            } else {
                $modules->scan();
            }
        } else {
            $modules->manual();
        }
    }

    /**
     * Return the Modules Collection
     *
     * @return ModuleCollection
     */
    public function modules()
    {
        return $this->modules;
    }

    /**
     * Return a single Module
     *
     * @param  string $id
     * @return Module
     */
    public function module($id)
    {
        return isset($this->modules[$id]) ? $this->modules[$id] : null;
    }

    /**
     * Scan the Module folder and add valid Modules to the collection
     * @return array
     */
    public function scan()
    {
        $files = $this->app['files'];

        // Get the Modules directory paths.
        $modulesPaths = $this->app['config']['modules.path'];

        if (! is_array($modulesPaths)) $modulesPaths = array($modulesPaths);

        // Now prepare an array with all directories.
        $paths = array();

        foreach ($modulesPaths as $modulesPath) {
            $paths[$modulesPath] = $files->directories(base_path($modulesPath));
        }

        if (! empty($paths)) {
            foreach ($paths as $path => $directories) {
                if (! empty($directories)) {
                    foreach ($directories as $directory) {
                        // Check if dir contains a module definition file
                        if ($files->exists($directory .DS .'module.json') {
                            $name = pathinfo($directory, PATHINFO_BASENAME);

                            $this->modules[$name] = new Module($name, $directory, null, $this->app, $path);
                        }
                    }
                }
            }

            // Save the manifest file
            $this->saveManifest();
        }

        return $this->modules;
    }

    /**
     * Get modules from config array
     * @return array
     */
    public function manual($config = null)
    {
        if (! is_null($config)) {
            $this->createInstances($config);
        } else {
            $moduleGroups = $this->app['config']['modules.modules'];

            if (! empty($moduleGroups)) {
                foreach ($moduleGroups as $group => $modules) {
                    $this->createInstances($modules, $group);
                }
            }
        }

        return $this->modules;
    }

    /**
     * Create module instances
     * @param array $modules
     * @param string|null $groupPath
     * @return array
     */
    public function createInstances($modules, $groupPath = null)
    {
        foreach ($modules as $key => $module) {
            // Get the name and definition.
            if (is_string($module)) {
                $name = $module;

                $definition = array();
            } else if (is_array($module)) {
                $name = $key;

                $definition = $module;
            }

            // Get Group. Manifest mode has the Group defined on the Module.
            $group = ! is_null($groupPath) ? $groupPath : $module['group'];

            // The path
            $path = base_path($group .DS . $name);

            // Create the instance
            $this->modules[$name] = new Module($name, $path, $definition, $this->app, $group);
        }
    }

    /**
     * Return manifest object
     * @return Manifest
     */
    public function manifest($module = null)
    {
        return $this->manifest->toArray($module);
    }

    /**
     * Save the manifest file
     * @param  array $modules
     * @return void
     */
    public function saveManifest($modules = null)
    {
        $this->manifest->save($this->modules);
    }

    /**
     * Delete the manifest file
     * @return void
     */
    public function deleteManifest()
    {
        $this->manifest->delete();
    }

    /**
     * Register all modules in collection
     * @return void
     */
    public function register()
    {
        return $this->modules->registerModules();
    }

    /**
     * Log a debug message
     * @param  string $message
     * @return void
     */
    public function logDebug($message)
    {
        $this->log($message);
    }

    /**
     * Log an error message
     * @param  string $message
     * @return void
     */
    public function logError($message)
    {
        $this->log($message, 'error');
    }

    /**
     * Log a message
     * @param  string $type
     * @param  string $message
     * @return void
     */
    public function log($message, $type = 'debug')
    {
        $config = $this->app['config'];

        if ($config->get('modules.debug')) {
            $log = $this->app['log'];

            $namespace = 'MODULES';

            $message = "[$namespace] $message";

            if ($type == 'error') {
                $log->error($message);
            } else {
                $log->debug($message);
            }
        }
    }

    /**
     * Prettify a JSON Encode
     * @param  mixed $values
     * @return string
     */
    public function prettyJsonEncode($values)
    {
        return json_encode($values, JSON_PRETTY_PRINT);
    }

}
