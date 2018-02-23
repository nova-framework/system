<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\BroadcasterInterface;
use Nova\Http\Request;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


abstract class Broadcaster implements BroadcasterInterface
{
    /**
     * The registered channel authenticators.
     *
     * @var array
     */
    protected $channels = array();


    /**
     * Register a channel authenticator.
     *
     * @param  string  $channel
     * @param  callable  $callback
     * @return $this
     */
    public function channel($channel, callable $callback)
    {
        $this->channels[$channel] = $callback;

        return $this;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Nova\Http\Request  $request
     * @param  string  $channel
     * @return mixed
     */
    protected function verifyUserCanAccessChannel(Request $request, $channel)
    {
        foreach ($this->channels as $pattern => $callback) {
            $regexp = preg_replace('/\{(.*?)\}/', '(?<$1>[^\.]+)', $pattern);

            if (preg_match('/^' .$regexp .'$/', $channel, $matches) !== 1) {
                continue;
            }

            $parameters = array_filter($matches, function ($key)
            {
                return ! is_numeric($key);

            }, ARRAY_FILTER_USE_KEY);

            array_unshift($parameters, $request->user());

            $result = call_user_func_array($callback, $parameters);

            if (! is_null($result)) {
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new AccessDeniedHttpException;
    }
}
