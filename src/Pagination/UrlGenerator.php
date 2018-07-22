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
    public function pageUrl($page, $path, array $query, $fragment)
    {
        $pageName = $this->getPageName();

        return $this->buildUrl(
            $path, array_merge($query, array($pageName => $page)), $fragment
        );
    }

    /**
     * Build the full query portion of a URL.
     *
     * @param  string  $path
     * @param  array  $query
     * @param  string|null  $fragment
     * @return string
     */
    protected function buildUrl($path, array $query, $fragment)
    {
        if (! empty($query)) {
            $separator = Str::contains($path, '?') ? '&' : '?';

            $path .= $separator .http_build_query($query, '', '&');
        }

        if (! empty($fragment)) {
            $path .= '#' .$fragment;
        }

        return $path;
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
