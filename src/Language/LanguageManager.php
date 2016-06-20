<?php

namespace Nova\Language;

use Nova\Language\Language;
use Nova\Support\Facades\Session;


class LanguageManager
{
    /**
     * The Application instance.
     *
     * @var \Foundation\Application
     */
    protected $app;

    /**
     * The active Language instances.
     *
     * @var array
     */
    protected $instances = array();

    /**
     * Create new Language Manager instance.
     *
     * @param  \core\Application  $app
     * @return void
     */
    function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get instance of Language with domain and code (optional).
     * @param string $domain Optional custom domain
     * @param string $code Optional custom language code.
     * @return Language
     */
    public function instance($domain = 'app', $code = LANGUAGE_CODE)
    {
        $code = $this->getCurrentLanguage($code);

        // The ID code is something like: 'en/system', 'en/app' or 'en/file_manager'
        $id = $code .'/' .$domain;

        // Initialize the domain instance, if not already exists.
        if (! isset($this->instances[$id])) {
            $this->instances[$id] = new Language($domain, $code);
        }

        return $this->instances[$id];
    }

    /**
     * Get current Language
     * @return string
     */
    protected function getCurrentLanguage($code)
    {
        // Check if the end-user do not ask for a custom code.
        if (($code == LANGUAGE_CODE) && Session::has('language')) {
            return Session::get('language', $code);
        }

        return $code;
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
