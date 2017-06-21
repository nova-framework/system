<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;


class NullBroadcaster extends Broadcaster
{

	/**
	 * {@inheritdoc}
	 */
	public function authenticate($request)
	{
		//
	}

	/**
	 * {@inheritdoc}
	 */
	public function validAuthenticationResponse($request, $result)
	{
		//
	}

	/**
	 * {@inheritdoc}
	 */
	public function broadcast(array $channels, $event, array $payload = array())
	{
		//
	}
}
