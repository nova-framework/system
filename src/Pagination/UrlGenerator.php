<?php

namespace Nova\Pagination;

use Nova\Support\Str;


class UrlGenerator
{
    /**
     * The query string variable used to store the page.
     *
     * @var string
     */
    protected $pageName = 'page';


    /**
     * Create a new URL Generator instance.
     *
     * @param  string  $path
     * @param  string  $pageName
     * @return void
     */
    public function __construct($pageName)
    {
        $this->pageName = $pageName;
    }

    /**
     * Resolve the URL for a given page number.
     *
     * @param  int  $page
     * @param  string  $path
     * @param  array  $query
     * @param  string|null  $fragment
     * @return string
     */
    public function resolve($page, $path, array $query, $fragment)
    {
        $pageName = $this->getPageName();

        //
        $parameters = array_merge($query, array($pageName => $page));

        return $path .$this->buildQuery($parameters, $path) .$this->buildFragment($fragment);
    }

    /**
     * Build the full query portion of a URL.
     *
     * @param  array  $query
     * @param  string  $path
     * @return string
     */
    protected function buildQuery($query, $path = '/')
    {
        if (! empty($query)) {
            $separator = Str::contains($path, '?') ? '&' : '?';

            return $separator .http_build_query($query, '', '&');
        }

        return '';
    }

    /**
     * Build the full fragment portion of a URL.
     *
     * @param  string|null  $fragment
     * @return string
     */
    protected function buildFragment($fragment)
    {
        if (! empty($fragment)) {
            return '#' .$fragment;
        }

        return  '';
    }

    /**
     * Set the query string variable used to store the page.
     *
     * @param  string  $name
     * @return $this
     */
    public function setPageName($name)
    {
        $this->pageName = $name;

        return $this;
    }

    /**
     * Get the query string variable used to store the page.
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }
}
