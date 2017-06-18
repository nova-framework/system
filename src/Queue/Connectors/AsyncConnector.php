<?php

namespace Nova\Queue\Connectors;

use Nova\Queue\Connectors\DatabaseConnector;
use Nova\Queue\Queues\AsyncQueue;
use Nova\Support\Arr;

class AsyncConnector extends DatabaseConnector
{

	/**
	 * Establish a queue connection.
	 *
	 * @param array $config
	 *
	 * @return \Nova\Queue\Contracts\QueueInterface
	 */
	public function connect(array $config)
	{
		$connection = Arr::get($config, 'connection');

		return new AsyncQueue(
			$this->connections->connection($connection),

			$config['table'],
			$config['queue'],

			Arr::get($config, 'expire', 60),
			Arr::get($config, 'binary', 'php'),
			Arr::get($config, 'binary_args', ''),
			Arr::get($config, 'connection_name', '')
		);
	}
}
