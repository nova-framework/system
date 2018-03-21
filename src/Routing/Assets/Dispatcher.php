<?php

namespace Nova\Routing\Assets;

use Nova\Container\Container;
use Nova\Filesystem\Filesystem;
use Nova\Foundation\Application;
use Nova\Http\JsonResponse;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Support\Arr;
use Nova\Support\Str;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

use Carbon\Carbon;

use Closure;
use LogicException;


class Dispatcher
{
    /**
     * The Application instance.
     *
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * All of the registered Asset Routes.
     *
     * @var array
     */
    protected $routes = array();

    /**
     * The valid Vendor paths.
     * @var array
     */
    protected $paths;

    /**
     * All of the named path hints.
     *
     * @var array
     */
    protected $hints = array();


    /**
     * Create a new Default Dispatcher instance.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register a new Asset Route with the manager.
     *
     * @param  string  $pattern
     * @param  \Closure  $callback
     * @return void
     */
    public function route($pattern, $callback)
    {
        $this->routes[$pattern] = $callback;
    }

    /**
     * Dispatch a Assets File Response.
     *
     * For proper Assets serving, the file URI should be either of the following:
     *
     * /assets/css/style.css
     * /plugins/blog/assets/css/style.css
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function dispatch(SymfonyRequest $request)
    {
        if (! is_null($route = $this->findRoute($request))) {
            list($callback, $parameters) = $route;

            array_unshift($parameters, $request);

            return call_user_func_array($callback, $parameters);
        }
    }

    /**
     * Dispatch an URI and return the associated file path.
     *
     * @param  string  $uri
     * @return string|null
     */
    protected function findRoute(Request $request)
    {
        if (! in_array($request->method(), array('GET', 'HEAD', 'OPTIONS'))) {
            return;
        }

        $uri = $request->path();

        foreach ($this->routes as $pattern => $callback) {
            if (preg_match('#^' .$pattern .'$#s', $uri, $matches)) {
                return array($callback, array_slice($matches, 1));
            }
        }
    }

    /**
     * Serve a File.
     *
     * @param  string  $path
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $disposition
     * @param  string|null  $fileName
     * @param  bool  $prepared
     *
     * @return  \Symfony\Component\HttpFoundation\Response
     */
    public function serve($path, SymfonyRequest $request, $disposition = 'inline', $fileName = null, $prepared = true)
    {
        if (! file_exists($path)) {
            return new Response('File Not Found', 404);
        } else if (! is_readable($path)) {
            return new Response('Unauthorized Access', 403);
        }

        // Create a Binary File Response instance.
        $headers = array(
            'Access-Control-Allow-Origin' => '*',
        );

        $mimeType = $this->guessMimeType($path);

        if ($request->getMethod() == 'OPTIONS') {
            $headers = array_merge($headers, array(
                'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, X-Auth-Token, Origin',
            ));

            return new Response('OK', 200, $headers);
        }

        // Not an OPTIONS request.
        else {
            $headers['Content-Type'] = $mimeType;
        }

        if ($mimeType !== 'application/json') {
            $response = new BinaryFileResponse($path, 200, $headers, true, $disposition, true, false);

            // Set the Content Disposition.
            $response->setContentDisposition($disposition, $fileName ?: basename($path));

            // Setup the (browser) Cache Control.
            $this->setupCacheControl($response);

            // Setup the Not Modified since...
            $response->isNotModified($request);
        } else {
            // We will do a special processing for the JSON files.
            $response = new JsonResponse(
                json_decode(file_get_contents($path), true), 200, $headers
            );
        }

        // Prepare the Response against the Request instance, if is requested.
        if ($prepared) {
            return $response->prepare($request);
        }

        return $response;
    }

    protected function setupCacheControl(SymfonyResponse $response)
    {
        $options = $this->app['config']->get('routing.assets.cache', array());

        //
        $ttl    = array_get($options, 'ttl', 600);
        $maxAge = array_get($options, 'maxAge', 10800);

        $sharedMaxAge = array_get($options, 'sharedMaxAge', 600);

        //
        $response->setTtl($ttl);
        $response->setMaxAge($maxAge);
        $response->setSharedMaxAge($sharedMaxAge);
    }

    protected function guessMimeType($path)
    {
        // Even the Symfony's HTTP Foundation have troubles with the CSS and JS files?
        //
        // Hard coding the correct mime types for presently needed file extensions.

        switch ($fileExt = pathinfo($path, PATHINFO_EXTENSION)) {
            case 'css':
                return 'text/css';

            case 'js':
                return 'application/javascript';

            case 'json':
                return 'application/json';

            case 'svg':
                return 'image/svg+xml';

            default:
                break;
        }

        // Guess the path's Mime Type.
        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($path);
    }

    public function getVendorPaths()
    {
        if (isset($this->paths)) {
            return $this->paths;
        }

        $files = $this->app['files'];

        // The cache file path.
        $path = STORAGE_PATH .'framework' .DS .'assets.php';

        // The config path for checking againts the cache file.
        $configPath = APPPATH .'Config' .DS .'Routing.php';

        $lastModified = $files->lastModified($configPath);

        if ($files->exists($path) && ! ($lastModified < $files->lastModified($path))) {
            return $this->paths = $files->getRequire($path);
        }

        $paths = array();

        $options = $this->app['config']->get('routing.assets.paths', array());

        foreach ($options as $vendor => $value) {
            $values = is_array($value) ? $value : array($value);

            $values = array_map(function($value) use ($vendor)
            {
                return $vendor .'/' .$value .'/';

            }, $values);

            $paths = array_merge($paths, $values);
        }

        $paths = array_unique($paths);

        // Save to the cache.
        $content = "<?php\n\nreturn " .var_export($paths, true) .";\n";

        $files->put($path, $content);

        return $this->paths = $paths;
    }

    /**
     * Register a Package for cascading configuration.
     *
     * @param  string  $package
     * @param  string  $hint
     * @param  string  $namespace
     * @return void
     */
    public function package($package, $hint, $namespace = null)
    {
        $namespace = $this->getPackageNamespace($package, $namespace);

        $this->addNamespace(str_replace('_', '-', $namespace), $hint);
    }

    /**
     * Return true if has the specified namespace hint on the router.
     *
     * @param  string  $namespace
     * @return void
     */
    public function hasNamespace($namespace)
    {
        $namespace = str_replace('_', '-', $namespace);

        return isset($this->hints[$namespace]);
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $namespace = str_replace('_', '-', $namespace);

        $this->hints[$namespace] = rtrim($hint, DS) .DS;
    }

    /**
     * Get the configuration namespace for a Package.
     *
     * @param  string  $package
     * @param  string  $namespace
     * @return string
     */
    protected function getPackageNamespace($package, $namespace)
    {
        if (is_null($namespace)) {
            list($vendor, $namespace) = explode('/', $package);

            return Str::snake($namespace);
        }

        return $namespace;
    }

    /**
     * Get the path for a registered namespace.
     *
     * @param  string  $namespace
     * @return string|null
     */
    public function getPackagePath($namespace)
    {
        $namespace = str_replace('_', '-', $namespace);

        return Arr::get($this->hints, $namespace);
    }

    /**
     * Returns all registered namespaces with the router.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->hints;
    }
}
