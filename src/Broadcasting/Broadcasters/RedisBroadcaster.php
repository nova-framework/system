<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Contracts\Broadcasting\BroadcasterInterface;
use Nova\Redis\Database as RedisDatabase;
use Nova\Support\Arr;


class RedisBroadcaster implements Broadcaster
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
	 * {@inheritdoc}
	 */
	public function broadcast(array $channels, $event, array $payload = array())
	{
		$connection = $this->redis->connection($this->connection);

		$payload = json_encode(array(
			'event'  => $event,
			'data'   => $payload,
			'socket' => Arr::pull($payload, 'socket'),
		));

		foreach ($channels as $channel) {
			$connection->publish($channel, $payload);
		}
	}
}
