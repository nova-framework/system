<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\Auth\Guest as GuestUser;
use Nova\Broadcasting\BroadcasterInterface;
use Nova\Broadcasting\Channels\PublicChannel as Channel;
use Nova\Container\Container;
use Nova\Database\ORM\Model;
use Nova\Database\ORM\ModelNotFoundException;
use Nova\Http\Request;
use Nova\Support\Arr;
use Nova\Support\Str;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;


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
     * The cached Guest instance, if any.
     *
     * @var \Nova\Broadcasting\Auth\Guest|null
     */
    protected $authGuest;


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
     * @return mixed
     */
    public function authenticate(Request $request)
    {
        $channelName = $request->input('channel_name');

        $channel = preg_replace('/^(private|presence)\-/', '', $channelName, 1, $count);

        if (($count == 1) && is_null($user = $request->user())) {
            // For the private and presence channels, the Broadcasting needs a valid User instance,
            // but it is not available for the non authenticated users (guests) within Auth System.
            // For the guests, we will use a cached GuestUser instance, with a random string as ID.

            $request->setUserResolver(function ()
            {
                return $this->resolveGuestUser();
            });
        }

        return $this->verifyUserCanAccessChannel($request, $channel);
    }

    /**
     * Resolve the Guest User instance with local caching.
     *
     * @return \Nova\Broadcasting\Auth\Guest
     */
    protected function resolveGuestUser()
    {
        if (isset($this->authGuest)) {
            return $this->authGuest;
        }

        $session = $this->container['session'];

        if (empty($id = $session->get('broadcasting.guest'))) {
            $id = dechex(time()) . Str::random(16);

            $session->set('broadcasting.guest', $id);
        }

        return $this->authGuest = new GuestUser($id);
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

            $parameters = array_filter($matches, function ($value, $key)
            {
                return is_string($key) && ! empty($value);

            }, ARRAY_FILTER_USE_BOTH);

            // The first parameter is always the authenticated User instance.
            array_unshift($parameters, $request->user());

            if ($result = $this->callChannelCallback($callback, $parameters)) {
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new AccessDeniedHttpException;
    }

    /**
     * Call a channel callback with the dependencies.
     *
     * @param  mixed  $callback
     * @param  array  $parameters
     * @return mixed
     */
    protected function callChannelCallback($callback, $parameters)
    {
        if (is_string($callback)) {
            list ($className, $method) = Str::parseCallback($callback, 'join');

            $callback = array(
                $instance = $this->container->make($className), $method
            );

            $reflector = new ReflectionMethod($instance, $method);
        } else {
            $reflector = new ReflectionFunction($callback);
        }

        return call_user_func_array(
            $callback, $this->resolveCallDependencies($parameters, $reflector)
        );
    }

    /**
     * Resolve the given method's type-hinted dependencies.
     *
     * @param  array  $parameters
     * @param  \ReflectionFunctionAbstract  $reflector
     * @return array
     */
    public function resolveCallDependencies(array $parameters, ReflectionFunctionAbstract $reflector)
    {
        foreach ($reflector->getParameters() as $key => $parameter) {
            if ($key === 0) {
                // The first parameter is always the authenticated User instance.
                continue;
            }

            $instance = $this->transformDependency($parameter, $parameters);

            if (! is_null($instance)) {
                array_splice($parameters, $key, 0, array($instance));
            }
        }

        return $parameters;
    }

    /**
     * Attempt to transform the given parameter into a class instance.
     *
     * @param  \ReflectionParameter  $parameter
     * @param  string  $name
     * @param  array  $parameters
     * @return mixed
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters)
    {
        if (is_null($class = $parameter->getClass())) {
            return;
        }

        // The parameter references a class.
        else if (! $class->isSubclassOf(Model::class)) {
            return $this->container->make($class->name);
        }

        $identifier = Arr::first($parameters, function ($parameterKey) use ($parameter)
        {
            return $parameterKey === $parameter->name;
        });

        if (! is_null($identifier)) {
            $instance = $class->newInstance();

            return call_user_func(array($instance, 'find'), $identifier);
        }
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
