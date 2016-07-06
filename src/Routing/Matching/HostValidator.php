<?php

namespace Nova\Routing\Matching;

use Nova\Http\Request;
use Nova\Routing\Route;


class HostValidator implements ValidatorInterface
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
        if (is_null($route->getCompiled()->getHostRegex())) return true;

        return preg_match($route->getCompiled()->getHostRegex(), $request->getHost());
    }

}
