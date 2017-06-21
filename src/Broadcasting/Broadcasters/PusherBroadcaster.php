<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Contracts\BroadcasterInterface;
use Nova\Support\Arr;

use Pusher;


class PusherBroadcaster implements BroadcasterInterface
{
	/**
	 * The Pusher SDK instance.
	 *
	 * @var \Pusher
	 */
	protected $pusher;

	/**
	 * Create a new broadcaster instance.
	 *
	 * @param  \Pusher  $pusher
	 * @return void
	 */
	public function __construct(Pusher $pusher)
	{
		$this->pusher = $pusher;
	}

	/**
	 * {@inheritdoc}
	 */
	public function broadcast(array $channels, $event, array $payload = array())
	{
		$socket = Arr::pull($payload, 'socket');

		$this->pusher->trigger($channels, $event, $payload, $socket);
	}

	/**
	 * Get the Pusher SDK instance.
	 *
	 * @return \Pusher
	 */
	public function getPusher()
	{
		return $this->pusher;
	}
}
