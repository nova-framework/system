<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;
use Nova\Redis\Database as RedisDatabase;
use Nova\Support\Arr;


class RedisBroadcaster extends Broadcaster
{
	/**
	 * The Redis instance.
	 *
	 * @var \Nova\Redis\Database
	 */
	protected $redis;

	/**
	 * The Redis connection to use for broadcasting.
	 *
	 * @var string
	 */
	protected $connection;


	/**
	 * Create a new broadcaster instance.
	 *
	 * @param  \Nova\Contracts\Redis\Database  $redis
	 * @param  string  $connection
	 * @return void
	 */
	public function __construct(RedisDatabase $redis, $connection = null)
	{
		$this->redis = $redis;

		$this->connection = $connection;
	}

	/**
	 * Authenticate the incoming request for a given channel.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return mixed
	 */
	public function authenticate($request)
	{
		$channelName = $request->input('channel_name');

		//
		$count = 0;

		$channel = preg_replace('/^(private|presence)\-/', '', $channelName, -1, $count);

		if (($count > 0) && is_null($user = $request->user())) {
			throw new HttpException(403);
		}

		return $this->verifyUserCanAccessChannel($request, $channel);
	}

	/**
	 * Return the valid authentication response.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  mixed  $result
	 * @return mixed
	 */
	public function validAuthenticationResponse($request, $result)
	{
		if (is_bool($result)) {
			return json_encode($result);
		}

		return json_encode(array(
			'channel_data' => array(
				'user_id'   => $request->user()->getAuthIdentifier(),
				'user_info' => $result,
			),
		));
	}

	/**
	 * Broadcast the given event.
	 *
	 * @param  array  $channels
	 * @param  string  $event
	 * @param  array  $payload
	 * @return void
	 */
	public function broadcast(array $channels, $event, array $payload = array())
	{
		$connection = $this->getConnection();

		//
		$socket = Arr::pull($payload, 'socket');

		$payload = json_encode(array(
			'event'  => $event,
			'data'   => $payload,
			'socket' => $socket,
		));

		foreach ($this->formatChannels($channels) as $channel) {
			$connection->publish($channel, $payload);
		}
	}

	/**
	 * Get the Redis single connection implementation.
	 *
	 * @return \Predis\Connection\SingleConnectionInterface
	 */
	public function getConnection()
	{
		return $this->redis->connection($this->connection);
	}
}
