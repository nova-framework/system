<?php

namespace Nova\Routing\Assets;

use Nova\Container\Container;
use Nova\Filesystem\Filesystem;
use Nova\Foundation\Application;
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
        if (! in_array($request->method(), array('GET', 'HEAD'))) {
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
            'Content-Type' => $this->getMimeType($path)
        );

        $response = new BinaryFileResponse($path, 200, $headers, true, $disposition, true, false);

        // Set the Content Disposition.
        $response->setContentDisposition($disposition, $fileName ?: basename($path));

        // Setup the (browser) Cache Control.
        $this->setupCacheControl($response);

        // Setup the Not Modified since...
        $response->isNotModified($request);

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

    protected function getMimeType($path)
    {
        // Even the Symfony's HTTP Foundation have troubles with the CSS and JS files?
        //
        // Hard coding the correct mime types for presently needed file extensions.

        switch ($fileExt = pathinfo($path, PATHINFO_EXTENSION)) {
            case 'css':
                return 'text/css';

            case 'js':
                return 'application/javascript';

            case 'svg':
                return 'image/svg+xml';

            default:
                break;
        }

        // Guess the path's Mime Type.
        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($path);
    }

    public function getPaths()
    {
        if (isset($this->paths)) {
            return $this->paths;
        }

        $files = $this->app['files'];

        // The cache file path.
        $path = STORAGE_PATH .'assets.php';

        // The config path for checking againts the cache file.
        $configPath = APPDIR .'Config' .DS .'Routing.php';

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
}
