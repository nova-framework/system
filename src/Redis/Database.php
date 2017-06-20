<?php

namespace Nova\Redis;

use Nova\Support\Arr;

use Predis\Client;

use Closure;


class Database
{
	/**
	 * The host address of the database.
	 *
	 * @var array
	 */
	protected $clients;

	/**
	 * Create a new Redis connection instance.
	 *
	 * @param  array  $servers
	 * @return void
	 */
	public function __construct(array $servers = array())
	{
		$cluster = Arr::pull($servers, 'cluster');

		$options = array_merge(array('timeout' => 10.0), (array) Arr::pull($servers, 'options'));

		if ($cluster) {
			$this->clients = $this->createAggregateClient($servers);
		} else {
			$this->clients = $this->createSingleClients($servers);
		}
	}

	/**
	 * Create a new aggregate client supporting sharding.
	 *
	 * @param  array  $servers
	 * @return array
	 */
	protected function createAggregateClient(array $servers)
	{
		$servers = Arr::except($servers, array('cluster'));

		return array('default' => new Client(array_values($servers)));
	}

	/**
	 * Create an array of single connection clients.
	 *
	 * @param  array  $servers
	 * @return array
	 */
	protected function createSingleClients(array $servers)
	{
		$clients = array();

		foreach ($servers as $key => $server) {
			$clients[$key] = new Client($server);
		}

		return $clients;
	}

	/**
	 * Get a specific Redis connection instance.
	 *
	 * @param  string  $name
	 * @return \Predis\Connection\SingleConnectionInterface
	 */
	public function connection($name = 'default')
	{
		if (is_null($name)) {
			$name = 'default';
		}

		return $this->clients[$name];
	}

	/**
	 * Run a command against the Redis database.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function command($method, array $parameters = array())
	{
		$instance = $this->clients['default'];

		return call_user_func_array(array($instance, $method), $parameters);
	}

	/**
	 * Subscribe to a set of given channels for messages.
	 *
	 * @param  array|string  $channels
	 * @param  \Closure  $callback
	 * @param  string  $connection
	 * @param  string  $method
	 * @return void
	 */
	public function subscribe($channels, Closure $callback, $connection = null, $method = 'subscribe')
	{
		$loop = $this->connection($connection)->pubSubLoop();

		call_user_func_array(array($loop, $method), (array) $channels);

		foreach ($loop as $message) {
			if (($message->kind === 'message') || ($message->kind === 'pmessage')) {
				call_user_func($callback, $message->payload, $message->channel);
			}
		}

		unset($loop);
	}

	/**
	 * Subscribe to a set of given channels with wildcards.
	 *
	 * @param  array|string  $channels
	 * @param  \Closure  $callback
	 * @param  string  $connection
	 * @return void
	 */
	public function psubscribe($channels, Closure $callback, $connection = null)
	{
		return $this->subscribe($channels, $callback, $connection, __FUNCTION__);
	}

	/**
	 * Dynamically make a Redis command.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->command($method, $parameters);
	}

}
