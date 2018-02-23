<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\BroadcasterInterface;
use Nova\Broadcasting\Channel;
use Nova\Container\Container;
use Nova\Http\Request;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


abstract class Broadcaster implements BroadcasterInterface
{
    /**
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The registered channel authenticators.
     *
     * @var array
     */
    protected $channels = array();


    /**
     * Create a new broadcaster instance.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

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

            //
            $handler = $this->makeChannelHandler($callback);

            if ($result = call_user_func_array($handler, $parameters)) {
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new AccessDeniedHttpException;
    }

    /**
     * Create a Channel handler callable.
     *
     * @param  mixed   $handler
     * @return mixed
     */
    public function makeChannelHandler($handler)
    {
        if (is_string($handler)) {
            return $this->createClassHandler($handler);
        }

        return $handler;
    }

    /**
     * Create a class based handler using the IoC container.
     *
     * @param  mixed    $handler
     * @return \Closure
     */
    public function createClassHandler($handler)
    {
        return function () use ($handler)
        {
            list($className, $method) = array_pad(explode('@', $handler, 2), 2, 'join');

            $instance = $this->container->make($className);

            // We will make a callable of the handler instance and a method that should
            // be called on that instance, then we will pass in the arguments that we
            // received in this method into this handler class instance's methods.
            $parameters = func_get_args();

            return call_user_func_array($instance, $parameters);
        };
    }

    /**
     * Format the channel array into an array of strings.
     *
     * @param  array  $channels
     * @return array
     */
    protected function formatChannels(array $channels)
    {
        return array_map(function ($channel)
        {
            if ($channel instanceof Channel) {
                return $channel->getName();
            }

            return $channel;

        }, $channels);
    }
}
