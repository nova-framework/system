<?php

namespace Nova\Assets;

use Nova\Foundation\Application;

use JShrink\Minifier as JShrink;


class AssetsManager
{
    /**
     * The valid Vendor paths.
     * @var array
     */
    protected $paths = array();

    /**
     * The Nova Config Repository instance
     *
     * @var \Nova\Config\Repository
     */
    protected $config;

    /**
     * The Nova Filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The assets cache base URI
     *
     * @var string
     */
    protected $baseUri;

    /**
     * The assets cache base directory
     *
     * @var string
     */
    protected $basePath;

    /**
     * @var array Asset templates
     */
    protected static $templates = array(
        'js'  => '<script src="%s" type="text/javascript"></script>',
        'css' => '<link href="%s" rel="stylesheet" type="text/css">'
    );


    /**
     * Create a new Assets Manager instance.
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->config = $app['config'];

        $this->files = $app['files'];

        // Prepare the base URI (for cache files).
        $this->baseUri = $this->config->get('assets.cache.baseUri', 'cache');

        // Prepare the base path (for cache files).
        $basePath = str_replace('/', DS, $this->baseUri);

        $this->basePath = PUBLICDIR .$basePath .DS;

        // Prepare the valid vendor paths.
        $paths = $this->config->get('assets.paths', array());

        $this->paths = $this->parsePaths($paths);
    }

    /**
     * Cleanup the Assets Cache directory.
     *
     * @return void
     */
    public function cleanup()
    {
        $search = $this->basePath .'cache-*';

        $paths = $this->files->glob($search);

        // Errors checking.
        if ($paths === false) return;
        
        foreach ($paths as $path) {
            if ($this->validate($path)) continue;

            $this->files->delete($path);
        }
    }

    public function getFilePath($uri)
    {
        if (preg_match('#^(templates|modules)/([^/]+)/assets/(.*)$#i', $uri, $matches)) {
            $baseName = strtolower($matches[1]);

            //
            $folder = $matches[2];

            if (($folder == 'adminlte') && ($baseName == 'templates')) {
                // The Asset path is on the AdminLTE Template.
                $folder = 'AdminLTE';
            } else if (strlen($folder) > 3) {
                // A standard Template or Module name.
                $folder = studly_case($folder);
            } else {
                // A short Template or Module name.
                $folder = strtoupper($folder);
            }

            $path = str_replace('/', DS, $matches[3]);

            // Calculate the base path.
            if ($baseName == 'modules') {
                $basePath = $this->config->get('modules.path', APPDIR .'Modules');
            } else {
                $basePath = APPDIR .'Templates';
            }

            $filePath = $basePath .DS .$folder .DS .'Assets' .DS .$path;
        } else if (preg_match('#^(assets|vendor)/(.*)$#i', $uri, $matches)) {
            $baseName = strtolower($matches[1]);

            //
            $path = $matches[2];

            if (($baseName == 'vendor') && ! starts_with($path, $this->paths)) {
                // The current URI is not a valid Asset File path on Vendor.
                return null;
            }

            $filePath = ROOTDIR .$baseName .DS .str_replace('/', DS, $path);
        } else {
            // The current URI is not a valid Asset File path.
            return null;
        }

        return $filePath;
    }

    /**
     * Load JS scripts.
     *
     * @param string|array $files The paths to resource files.
     * @param bool         $cached Wheter or not the caching is active.
     */
    public function js($files, $cached = true)
    {
        return $this->resource($files, $cached, 'js');
    }

    /**
     * Load CSS scripts.
     *
     * @param string|array $files The paths to resource files.
     * @param bool         $cached Wheter or not the caching is active.
     */
    public function css($files, $cached = true)
    {
        return $this->resource($files, $cached, 'css');
    }

    /**
     * Fetch CSS or JS scripts.
     *
     * @param string       $type The resource's type.
     * @param string|array $files The paths to resource files.
     * @param bool         $cached Wheter or not the caching is active.
     */
    public function fetch($type, $files, $cached = true)
    {
        return $this->resource($files, $cached, $type, true);
    }

    /**
     * Common templates for assets.
     *
     * @param string|array $files
     * @param string       $mode
     * @param bool         $fetch
     */
    protected function resource(array $data, $cached, $type, $fetch = false)
    {
        $files = $this->processFiles($data, $type, $cached);

        //
        $result = '';

        foreach ($files as $file) {
            $result .= $this->render($file, $type);
        }

        if ($fetch) {
            // Return the resulted string, with no output.
            return $result;
        }

        // Output the resulted string if it is not empty.
        if (! empty($result)) {
            echo $result;
        }
    }

    protected function render($file, $type)
    {
        if (! empty($file) && isset(static::$templates[$type])) {
            $template = static::$templates[$type];

            return sprintf($template, $file) ."\n";
        }
    }

    protected function processFiles($files, $type, $cached)
    {
        $cacheActive = $this->config->get('assets.cache.active', false);

        $cacheActive = $cacheActive ? $cached : false;

        // Adjust the files parameter to array.
        $files = is_array($files) ? $files : array($files);

        // Filter the non empty entries from the files array.
        $files = array_filter($files, function($value)
        {
            return ! empty($value);
        });

        if (! $cacheActive || empty($files)) {
            // No further processing required.
            return $files;
        }

        // Split the files on local and remote ones.
        list ($result, $files) = $this->parseFiles($files);

        if (! empty($files)) {
            // Update the processed cache file.
            $cacheFile = $this->cache($type, $files);

            // Push the cache file URI to the result.
            $uri = $this->baseUri .'/' .$cacheFile;

            array_push($result, site_url($uri));
        }

        return $result;
    }

    protected function parseFiles(array $files)
    {
        $local  = array();
        $remote = array();

        //
        $siteUrl = $this->config['app.url'];

        foreach ($files as $file) {
            if (starts_with($file, $siteUrl)) {
                array_push($local, $file);
            } else {
                array_push($remote, $file);
            }
        }

        return array($remote, $local);
    }

    protected function cache($type, array $files)
    {
        $last = 0;

        $assets = array();

        foreach ($files as $file) {
            $uri = $this->assetUri($file);

            $path = $this->getFilePath($uri);

            if (is_null($path)) continue;

            if (is_readable($path)) {
                $assets[$file] = $path;

                //
                $lastModified = $this->files->lastModified($path);

                $last = max($last, $lastModified);
            }
        }

        $fileName = "cache-{$last}-" . md5(serialize($assets)) . ".{$type}";

        $filePath = $this->basePath .$fileName;

        if (! $this->validate($filePath)) {
            $chunks = array();

            foreach ($assets as $file => $path) {
                // Get the assets file contents.
                $content = file_get_contents($path);

                if ($type == 'css') {
                    $baseUrl = dirname($file);

                    // Adjust the relative URLs on the CSS.
                    $content = preg_replace('/url\((?:[\"\\\'])?([^\\\'\"\)]+)(?:[\"\\\'])?\)/i', 'url("' .$baseUrl .'/$1")', $content);

                    $content = str_replace($baseUrl .'/../', dirname($baseUrl) .'/', $content);

                    // Minify the CSS content and append it to result.
                    $chunks[] = static::compress($content);
                } else if ($type == 'js') {
                    // Minify the javascript content and append it to result.
                    $chunks[] = JShrink::minify($content);
                }
            }

            // Save the chunks on specified cache file.
            $content = implode("\n", $chunks);

            $this->files->put($filePath, $content);
        }

        return $fileName;
    }

    protected function validate($path)
    {
        if (! is_readable($path)) {
            return false;
        }

        $lifeTime = $this->config->get('assets.cache.lifeTime', 1440);

        // Retrieve the file timestamp.
        $lastModified = $this->files->lastModified($path);

        if ($lastModified > (time() - ($lifeTime * 60))) return true;

        return false;
    }

    protected static function compress($buffer)
    {
        // Remove comments.
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);

        // Remove tabs, spaces, newlines, etc.
        $buffer = str_replace(array("\r\n","\r","\n","\t",'  ','    ','     '), '', $buffer);

        // Remove other spaces before/after ';'.
        $buffer = preg_replace(array('(( )+{)','({( )+)'), '{', $buffer);
        $buffer = preg_replace(array('(( )+})','(}( )+)','(;( )*})'), '}', $buffer);
        $buffer = preg_replace(array('(;( )+)','(( )+;)'), ';', $buffer);

        return $buffer;
    }

    protected function parsePaths(array $paths)
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

    protected function assetUri($file)
    {
        $uri = parse_url($file, PHP_URL_PATH);

        return str_replace(array('//', '../'), '/', ltrim($uri, '/'));
    }

    /**
     * Magic Method for handling dynamic functions.
     *
     * @param  string  $method
     * @param  array   $params
     * @return mixed|void
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $params)
    {
        // Add the support for the dynamic fetchX Methods.
        if (starts_with($method, 'fetch')) {
            $type = lcfirst(substr($method, 5));

            // Prepend the type to parameters.
            array_unshift($params, $type);

            return call_user_func_array(array($this, 'fetch'), $params);
        }

        throw new \BadMethodCallException("Method [$method] does not exist on " .get_class($this));
    }

}
