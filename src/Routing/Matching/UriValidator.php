<?php

namespace Nova\Routing\Matching;

use Nova\Http\Request;
use Nova\Routing\Route;


class UriValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param  \Nova\Routing\Route  $route
     * @param  \Nova\Http\Request  $request
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        $path = $request->path() == '/' ? '/' : '/'.$request->path();

        return preg_match($route->getCompiled()->getRegex(), rawurldecode($path));
    }

}
