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

use LogicException;


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
     * @param  string $locale
     * @param  array $hints
     * @return void
     */
    function __construct(Application $app, $locale, array $hints)
    {
        $this->app = $app;

        $this->locale = $locale;

        $this->hints = $hints;

        // Setup the know Languages.
        $this->languages = $app['config']['languages'];
    }

    /**
     * Get instance of Language with domain and code (optional).
     * @param string $domain Optional custom domain
     * @param string $code Optional custom language code.
     * @return Language
     */
    public function instance($domain = 'app', $code = null)
    {
        if (is_null($code)) {
            $code = $this->getLocale();
        }

        // Check if the language code is known, with fallback to English.
        if (! isset($this->languages[$code])) {
            $code = 'en';
        }

        // Check if the requested domain is a known one.
        if (! isset($this->hints[$domain])) {
            throw new LogicException("Unknown language domain [$domain]");
        }

        // The ID code is something like: 'en/system', 'en/app' or 'en/file_manager'
        $id = sprintf('%s/%s', $code, $domain);

        // Returns the Language domain instance, if it already exists.
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $path = Arr::get($this->hints, $domain);

        $info = Arr::get($this->languages, $code);

        return $this->instances[$id] = new Language($this, $domain, $path, $code, $info);
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

        return $this->addNamespace($namespace, $hint);
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
        if (! is_null($namespace)) {
            return $namespace;
        }

        list ($vendor, $namespace) = explode('/', $package);

        return Str::snake($namespace);
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

        return $this;
    }

    /**
     * Returns all registered namespaces with the config loader.
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->hints;
    }

    /**
     * Returns all registered namespaces with the config loader.
     *
     * @param  string  $domain
     * @return string
     */
    public function getNamespace($domain)
    {
        return Arr::get($this->hints, $domain);
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

        // Retrieve the full qualified locale from languages list.
        $locale = Str::finish(
            Arr::get($this->languages, "{$locale}.locale", 'en_US'), '.utf8'
        );

        // Setup the PHP's Time locale.
        setlocale(LC_TIME, $locale);

        return $this;
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
