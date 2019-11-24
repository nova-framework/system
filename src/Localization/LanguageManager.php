<?php

namespace Nova\Localization;

use Nova\Foundation\Application;
use Nova\Localization\Language;
use Nova\Support\Arr;
use Nova\Support\Str;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;


class LanguageManager
{
    /**
     * The Application instance.
     *
     * @var \Nova\Foundation\Application
     */
    protected $app;

    /**
     * The default locale being used by the translator.
     *
     * @var string
     */
    protected $locale;

    /**
     * The know Languages.
     *
     * @var array
     */
    protected $languages = array();

    /**
     * The active Language instances.
     *
     * @var array
     */
    protected $instances = array();

    /**
     * All of the named path hints.
     *
     * @var array
     */
    protected $hints = array();


    /**
     * Create new Language Manager instance.
     *
     * @param  \core\Application  $app
     * @return void
     */
    function __construct(Application $app, $locale)
    {
        $this->app = $app;

        $this->locale = $locale;

        // Setup the know Languages.
        $this->languages = $app['config']['languages'];

        // Setup the default path hints.
        $this->hints = array(
            // Namespace for the Framework path.
            'nova' => dirname(__DIR__) .DS .'Language',

            // Namespace for the Application path.
            'app' => APPPATH .'Language',

            // Namespace for the Shared path.
            'shared' => BASEPATH .'shared' .DS .'Language',
        );
    }

    /**
     * Get instance of Language with domain and code (optional).
     * @param string $domain Optional custom domain
     * @param string $code Optional custom language code.
     * @return Language
     */
    public function instance($domain = 'app', $locale = null)
    {
        $locale = $locale ?: $this->locale;

        // The ID code is something like: 'en/system', 'en/app' or 'en/file_manager'
        $id = $locale .'/' .$domain;

        // Returns the Language domain instance, if it already exists.
        if (isset($this->instances[$id])) return $this->instances[$id];

        return $this->instances[$id] = new Language($this, $domain, $locale);
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

        $this->addNamespace($namespace, $hint);
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
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->hints[$namespace] = $hint;
    }

    /**
     * Returns all registered namespaces with the config
     * loader.
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->hints;
    }

    /**
     * Get the know Languages.
     *
     * @return string
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function locale()
    {
        return $this->getLocale();
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function setLocale($locale)
    {
        // Setup the Framework locale.
        $this->locale = $locale;

        // Setup the Carbon locale.
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        CarbonPeriod::setLocale($locale);
        CarbonInterval::setLocale($locale);

        // Retrieve the full locale from languages list.
        $locale = Arr::get($this->languages, $locale .'.locale', 'en_US');

        // Setup the PHP's time locale.
        setlocale(LC_TIME, $locale .'.utf8');
    }

    /**
     * Dynamically pass methods to the default instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array(array($this->instance(), $method), $parameters);
    }

}
