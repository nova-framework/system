<?php
/**
 * ServiceProvider - Implements a Service Provider.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Support;

use Nova\Support\Str;

use BadMethodCallException;
use ReflectionClass;


abstract class ServiceProvider
{
    /**
     * The Application instance.
     *
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The paths that should be published.
     *
     * @var array
     */
    protected static $publishes = array();


    /**
     * Create a new service provider instance.
     *
     * @param  \Nova\Foundation\Application     $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    abstract public function register();

    /**
     * Register the package's component namespaces.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @param  string  $path
     * @param  string  $type
     * @return void
     */
    public function package($package, $namespace = null, $path = null, $type = 'package')
    {
        $namespace = $this->getPackageNamespace($package, $namespace);

        //
        $files = $this->app['files'];

        // In this method we will register the configuration package for the package
        // so that the configuration options cleanly cascade into the application
        // folder to make the developers lives much easier in maintaining them.
        $path = $path ?: $this->guessPackagePath();

        // Register the Package Config path.
        $config = $path .DS .'Config';

        if ($files->isDirectory($config)) {
            $this->app['config']->package($package, $config, $namespace);
        }

        // Register the Package Language path.
        $language = $path .DS .'Language';

        if ($files->isDirectory($language)) {
            $this->app['language']->package($package, $language, $namespace);
        }

        // Register the Package Views path.
        $views = $this->app['view'];

        $appView = $this->getAppViewPath($package);

        if ($files->isDirectory($appView)) {
            $views->addNamespace($package, $appView);
        }

        $viewPath = $path .DS .'Views';

        if ($files->isDirectory($viewPath)) {
            $views->addNamespace($package, $viewPath);
        }

        //
        // Register the Package Assets path.

        if ($type === 'package') {
            $assets = dirname($path) .DS .'assets';

            //
            list ($vendor) = explode('/', $package);

            $namespace = sprintf('packages/%s/%s', Str::snake($vendor), $namespace);
        } else {
            $assets = $path .DS .'Assets';

            $namespace = Str::plural($type) .'/' .$namespace;
        }

        if ($files->isDirectory($assets)) {
            $this->app['assets.dispatcher']->package($package, $assets, $namespace);
        }
    }

    /**
     * Guess the package path for the provider.
     *
     * @return string
     */
    public function guessPackagePath()
    {
        $reflection = new ReflectionClass($this);

        $path = $reflection->getFileName();

        return realpath(dirname($path) .'/../');
    }

    /**
     * Determine the namespace for a package.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @return string
     */
    protected function getPackageNamespace($package, $namespace)
    {
        if (is_null($namespace)) {
            list ($vendor, $namespace) = explode('/', $package);

            return Str::snake($namespace);
        }

        return $namespace;
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param  string  $group
     * @return void
     */
    protected function publishes(array $paths, $group)
    {
        if (! array_key_exists($group, static::$publishes)) {
            static::$publishes[$group] = array();
        }

        static::$publishes[$group] = array_merge(static::$publishes[$group], $paths);
    }

    /**
     * Get the paths to publish.
     *
     * @param  string|null  $group
     * @return array
     */
    public static function pathsToPublish($group = null)
    {
        if (is_null($group)) {
            $paths = array();

            foreach (static::$publishes as $class => $publish) {
                $paths = array_merge($paths, $publish);
            }

            return array_unique($paths);
        } else if (array_key_exists($group, static::$publishes)) {
            return static::$publishes[$group];
        }

        return array();
    }

    /**
     * Register the package's custom Forge commands.
     *
     * @param  array  $commands
     * @return void
     */
    public function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        // To register the commands with Forge, we will grab each of the arguments
        // passed into the method and listen for Forge "start" event which will
        // give us the Forge console instance which we will give commands to.
        $events = $this->app['events'];

        $events->listen('forge.start', function($forge) use ($commands)
        {
            $forge->resolveCommands($commands);
        });
    }

    /**
     * Get the application package view path.
     *
     * @param  string  $package
     * @return string
     */
    protected function getAppViewPath($package)
    {
        return $this->app['path'] .str_replace('/', DS, "/Views/Packages/{$package}");
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }

    /**
     * Get the events that trigger this service provider to register.
     *
     * @return array
     */
    public function when()
    {
        return array();
    }

    /**
     * Determine if the provider is deferred.
     *
     * @return bool
     */
    public function isDeferred()
    {
        return $this->defer;
    }

    /**
     * Dynamically handle missing method calls.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method == 'boot') {
            return;
        }

        throw new BadMethodCallException("Call to undefined method [{$method}]");
    }
}
