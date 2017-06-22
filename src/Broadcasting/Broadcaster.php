<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\Contracts\BroadcasterInterface;

use Symfony\Component\HttpKernel\Exception\HttpException;

use ReflectionFunction;


abstract class Broadcaster implements BroadcasterInterface
{
	/**
	 * The registered channel authenticators.
	 *
	 * @var array
	 */
	protected $channels = array();

	/**
	 * The binding registrar (router) instance.
	 *
	 * @var BindingRegistrar
	 */
	protected $bindingRegistrar;


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
	protected function verifyUserCanAccessChannel($request, $channel)
	{
		$user = $request->user();

		foreach ($this->channels as $pattern => $callback) {
			$parameters = array();

			if (! $this->channelMatch($pattern, $channel, $parameters))) {
				continue;
			}

			$result = call_user_func_array(
				$callback, array_merge(array($user), $parameters)
			);

			if (! is_null($result) {
				return $this->validAuthenticationResponse($request, $result);
			}
		}

		throw new HttpException(403);
	}

	/**
	 * Matches the given pattern and channel, with parameters extraction.
	 *
	 * @param  string  $channel
	 * @param  string  $pattern
	 * @return array|null
	 */
	protected function channelMatch($pattern, $channel, array &$parameters)
	{
		if ($pattern === $channel) {
			// Direct match with no parameters.
			return true;
		}

		$count = 0;

		$regexp = preg_replace('/\{(.*?)\}/', '(?<$1>[^\.]+)', $pattern, -1, $count);

		if ($count === 0) {
			// The pattern has no parameters, then the matching will always fail.
			return false;
		} else if (preg_match('/^'. $regexp .'$/', , $channel, $matches) === 1) {
			$parameters = $this->gatherChannelParameters($matches);

			return true;
		}

		return false;
	}

	/**
	 *  Gather the channel parameters from a matches array.
	 *
	 * @param  array  $matches
	 * @return array
	 */
	protected function gatherChannelParameters(array $matches)
	{
		return array_filter($matches, function ($key)
		{
			return ! is_numeric($key);

		}, ARRAY_FILTER_USE_KEY);
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
			return $channel->getName();

		}, $channels);
	}
}
