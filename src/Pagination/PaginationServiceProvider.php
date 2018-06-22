<?php

namespace Nova\Pagination;

use Nova\Support\ServiceProvider;
use Nova\Support\Str;


class PaginationServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Paginator::viewFactoryResolver(function ()
        {
            return $this->app['view'];
        });

        Paginator::currentPathResolver(function ()
        {
            return $this->app['request']->url();
        });

        Paginator::currentPageResolver(function ($pageName = 'page')
        {
            $page = $this->app['request']->input($pageName);

            if ((filter_var($page, FILTER_VALIDATE_INT) !== false) && ((int) $page >= 1)) {
                return $page;
            }

            return 1;
        });

        Paginator::pageUrlResolver(function ($page, array $query, $pageName = 'page', $path = '/')
        {
            $query = array_merge($query, array($pageName => $page));

            if (count($query) > 0) {
                $path .= Str::contains($path, '?') ? '&' : '?';

                $path .= http_build_query($query, '', '&');
            }

            return $path;
        });
    }
}
