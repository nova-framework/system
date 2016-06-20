<?php

namespace Translation;


class Translator
{
    /**
     * The path to the translation files.
     *
     * @var string
     */
    protected $path;

    /**
     * The array of loaded translation groups.
     *
     * @var array
     */
    protected $loaded = array();

    /**
     * The default locale being used by the Translator.
     *
     * @var string
     */
    protected $locale;

    /**
     * The fallback locale used by the Translator.
     *
     * @var string
     */
    protected $fallback;

    /**
     * Create a new instance.
     *
     * @param  string  $path
     * @param  string  $locale
     * @return void
     */
    function __construct($path, $locale, $fallback)
    {
        $this->path = $path;

        $this->locale = $locale;

        $this->fallback = $fallback;
    }

    /**
     * Determine if a translation exists.
     *
     * @param  string  $key
     * @param  string  $locale
     * @return bool
     */
    public function has($key, $locale = null)
    {
        return $this->get($key, array(), $locale) !== $key;
    }

    /**
     * Get the translation for the given key.
     *
     * @param  string  $key
     * @param  array   $replace
     * @param  string  $locale
     * @return string
     */
    public function get($key, array $replace = array(), $locale = null)
    {
        @list($group, $item) = $this->parseKey($key);

        foreach ($this->parseLocale($locale) as $locale) {
            $this->load($group, $locale);

            if (empty($item)) {
                $line = $this->getGroup($group, $locale);

                if (!empty($line)) break;
            } else {
                $line = $this->getLine($group, $locale, $item, $replace);

                if (!is_null($line)) break;
            }
        }

        if (!isset($line)) return $key;

        return $line;
    }

    /**
     * Retrieve a language line out the loaded array.
     *
     * @param  string  $group
     * @param  string  $locale
     * @param  string  $item
     * @param  array   $replace
     * @return string|null
     */
    protected function getLine($group, $locale, $item, array $replace)
    {
        $line = array_get($this->loaded[$group][$locale], $item);

        if (is_string($line)) {
            return $this->makeReplacements($line, $replace);
        } elseif (is_array($line) && count($line) > 0) {
            return $line;
        }
    }

    protected function getGroup($group, $locale)
    {
        return $this->loaded[$group][$locale];
    }

    /**
     * Make the place-holder replacements on a line.
     *
     * @param  string  $line
     * @param  array   $replace
     * @return string
     */
    protected function makeReplacements($line, array $replace)
    {
        foreach ($replace as $key => $value) {
            $line = str_replace(':' .$key, $value, $line);
        }

        return $line;
    }

    /**
     * Get the translation for a given key.
     *
     * @param  string  $id
     * @param  array   $parameters
     * @param  string  $locale
     * @return string
     */
    public function trans($id, array $parameters = array(), $locale = null)
    {
        return $this->get($id, $parameters, $locale);
    }

    /**
     * Load the specified language group.
     *
     * @param  string  $group
     * @param  string  $locale
     * @return void
     */
    public function load($group, $locale)
    {
        if ($this->isLoaded($group, $locale)) return;

        $lines = array();

        $file = $this->path ."/{$locale}/{$group}.php";

        if (file_exists($file)) {
            $lines = include $file;
        }

        $this->loaded[$group][$locale] = $lines;
    }

    /**
     * Determine if the given group has been loaded.
     *
     * @param  string  $group
     * @param  string  $locale
     * @return bool
     */
    protected function isLoaded($group, $locale)
    {
        return isset($this->loaded[$group][$locale]);
    }


    /**
     * Parse a key into group, and item.
     *
     * @param  string  $key
     * @return array
     */
    protected function parseKey($key)
    {
        $segments = explode('.', $key);

        $group = $segments[0];

        unset($segments[0]);

        $segments = implode('.', $segments);

        return array($group, $segments);
    }

    /**
     * Get the array of locales to be checked.
     *
     * @return array
     */
    protected function parseLocale($locale)
    {
        if (! is_null($locale)) {
            return array_filter(array($locale, $this->fallback));
        } else {
            return array_filter(array($this->locale, $this->fallback));
        }
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
        $this->locale = $locale;
    }

    /**
     * Set the fallback locale being used.
     *
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * Set the fallback locale being used.
     *
     * @param  string  $fallback
     * @return void
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }
}
