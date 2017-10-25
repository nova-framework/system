<?php

namespace Nova\Routing\Assets;

use Nova\Container\Container;
use Nova\Filesystem\Filesystem;
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
     * All of the registered Asset Routes.
     *
     * @var array
     */
    protected $routes = array();


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
     * @param string $path
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serve($path, SymfonyRequest $request)
    {
        if (! is_file($path) || ! is_readable($path)) {
            return new Response('File Not Found', 404);
        }

        // Create a Binary File Response instance.
        $headers = array(
            'Content-Type' => $this->getMimeType($path)
        );

        $response = new BinaryFileResponse(
            $path, 200, $headers, true, 'inline', true, false
        );

        // Setup the (browser) Cache Control.
        $response->setTtl(600);
        $response->setMaxAge(10800);
        $response->setSharedMaxAge(600);

        // Prepare the Response against the Request instance.
        $response->isNotModified($request);

        return $response->prepare($request);
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
}
