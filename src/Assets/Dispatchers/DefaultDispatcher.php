<?php

namespace Nova\Assets\Dispatchers;

use Nova\Http\Response;
use Nova\Assets\DispatcherInterface;
use Nova\Support\Facades\Config;
use Nova\Support\Facades\Module;
use Nova\Support\Str;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

use Carbon\Carbon;

use LogicException;


class DefaultDispatcher implements DispatcherInterface
{
    /**
     * The valid Vendor paths.
     * @var array
     */
    protected $paths = array();

    /**
     * The cache control options.
     * @var int
     */
    protected $cacheControl = array();

    /**
     * Wheter or not the CSS and JS files are auto-compressed.
     * @var boolean
     */
    protected $compress = true;

    /**
     * The currently accepted encodings for Response content compression.
     *
     * @var array
     */
    protected static $algorithms = array('gzip', 'deflate');


    /**
     * Create a new Default Dispatcher instance.
     *
     * @return void
     */
    public function __construct()
    {
        $paths = Config::get('assets.paths', array());

        $this->paths = $this->parsePaths($paths);

        //
        $this->compress = Config::get('assets.compress', true);

        $this->cacheControl = Config::get('assets.cache', array());
    }

    /**
     * Dispatch a Assets File Response.
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function dispatch(SymfonyRequest $request)
    {
        // For proper Assets serving, the file URI should be either of the following:
        //
        // /templates/default/assets/css/style.css
        // /modules/blog/assets/css/style.css
        // /assets/css/style.css

        if (! in_array($request->method(), array('GET', 'HEAD'))) {
            // The Request Method is not valid for Asset Files.
            return null;
        }

        // Calculate the Asset File path, looking for a valid one.
        $uri = $request->path();

        if (preg_match('#^modules/([^/]+)/assets/(.*)$#i', $uri, $matches)) {
            $baseName = strtolower($matches[1]);

            //
            $pathName = $matches[1];

            if (strlen($pathName) > 3) {
                // A standard Template or Module name.
                $pathName = Str::studly($pathName);
            } else {
                // A short Template or Module name.
                $pathName = strtoupper($pathName);
            }

            $path = str_replace('/', DS, $matches[2]);

            // Calculate the base path.
            $module = Module::where('basename', $pathName);

            if ($module->isEmpty()) return null;

            $filePath = Module::resolveAssetPath($module, $path);
        } else if (preg_match('#^(assets|vendor)/(.*)$#i', $uri, $matches)) {
            $baseName = strtolower($matches[1]);

            //
            $path = $matches[2];

            if (($baseName == 'vendor') && ! Str::startsWith($path, $this->paths)) {
                // The current URI is not a valid Asset File path on Vendor.
                return null;
            }

            $filePath = BASEPATH .$baseName .DS .str_replace('/', DS, $path);
        } else {
            // The current URI is not a valid Asset File path.
            return null;
        }

        // Create a Response for the current Asset File path and return it.
        return $this->serve($filePath, $request);
    }

    /**
     * Serve a File.
     *
     * @param string $path
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function serve($path, SymfonyRequest $request)
    {
        if (! file_exists($path)) {
            return new Response('File Not Found', 404);
        } else if (! is_readable($path)) {
            return new Response('Unauthorized Access', 403);
        }

        // Retrieve the file content type.
        $mimeType = $this->getMimeType($path);

        // Calculate the available compression algorithms.
        $algorithms = $this->getEncodingAlgorithms($request);

        // Determine if the file could be compressed.
        $compressable = (($mimeType == 'application/javascript') || str_is('text/*', $mimeType));

        if ($this->compressFiles() && ! empty($algorithms) && $compressable) {
            // Get the (first) encoding algorithm.
            $algorithm = array_shift($algorithms);

            // Retrieve the file content.
            $content = file_get_contents($path);

            // Encode the content using the specified algorithm.
            $content = $this->encodeContent($content, $algorithm);

            // Retrieve the Last-Modified information.
            $timestamp = filemtime($path);

            $modifyTime = Carbon::createFromTimestampUTC($timestamp);

            $lastModified = $modifyTime->format('D, j M Y H:i:s') .' GMT';

            // Create the custom Response instance.
            $response = new Response($content, 200, array(
                'Content-Type'     => $mimeType,
                'Content-Encoding' => $algorithm,
                'Last-Modified'    => $lastModified,
            ));
        } else {
            // Create a Binary File Response instance.
            $response = new BinaryFileResponse($path, 200, array(), true, 'inline', true, false);

            // Set the Content type.
            $response->headers->set('Content-Type', $mimeType);
        }

        // Setup the (browser) Cache Control.
        $this->setupCacheControl($response);

        // Prepare against the Request instance.
        $response->isNotModified($request);

        return $response;
    }

    protected function setupCacheControl(SymfonyResponse $response)
    {
        $ttl    = array_get($this->cacheControl, 'ttl', 600);
        $maxAge = array_get($this->cacheControl, 'maxAge', 10800);

        $sharedMaxAge = array_get($this->cacheControl, 'sharedMaxAge', 600);

        //
        $response->setTtl($ttl);
        $response->setMaxAge($maxAge);
        $response->setSharedMaxAge($sharedMaxAge);
    }

    protected function encodeContent($content, $algorithm)
    {
        if ($algorithm == 'gzip') {
            return gzencode($content, -1, FORCE_GZIP);
        } else if ($algorithm == 'deflate') {
            return gzencode($content, -1, FORCE_DEFLATE);
        }

        throw new LogicException('Unknow encoding algorithm: ' .$algorithm);
    }

    protected function getEncodingAlgorithms(SymfonyRequest $request)
    {
        // Get the accepted encodings from the Request instance.
        $acceptEncoding = $request->headers->get('Accept-Encoding');

        if (is_null($acceptEncoding)) {
            // No encoding accepted?
            return array();
        }

        // Retrieve the accepted encoding values.
        $values = explode(',', $acceptEncoding);

        // Filter the meaningful values.
        $values = array_filter($values, function($value)
        {
            $value = trim($value);

            return ! empty($value);
        });

        return array_values(array_intersect($values, static::$algorithms));
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

            default:
                break;
        }

        // Guess the path's Mime Type.
        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($path);
    }

    private function parsePaths(array $paths)
    {
        $result = array();

        foreach ($paths as $vendor => $value) {
            $values = is_array($value) ? $value : array($value);

            $values = array_map(function($value) use ($vendor)
            {
                return $vendor .'/' .$value .'/';

            }, $values);

            $result = array_merge($result, $values);
        }

        return array_unique($result);
    }

    /**
     * Wheter or not the CSS and JS files are auto-compressed.
     *
     * @return boolean
     */
    public function compressFiles()
    {
        return $this->compress;
    }

    /**
     * Return the cache control options.
     *
     * @return array
     */
    public function getCacheControl()
    {
        return $this->cacheControl;
    }

}
