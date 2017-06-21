<?php

namespace Nova\Broadcasting;

use Nova\Broadcasting\Contracts\BroadcasterInterface;
use Nova\Http\Request;
use Nova\Routing\Controller;


class BroadcastController extends Controller
{
	/**
	 * The Broadcaster implementation.
	 */
	protected $broadcaster;


	/**
	 * Create a new Controller instance.
	 *
	 * @param  \Nova\Broadcasting\Contracts\BroadcasterInterface  $broadcaster
	 * @return void
	 */
	public function __construct(BroadcasterInterface $broadcaster)
	{
		$this->broadcaster = $broadcaster;
	}

	/**
	 * Authenticate the request for channel access.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @return \Nova\Http\Response
	 */
	public function authenticate(Request $request)
	{
		return $this->broadcaster->authenticate($request);
	}
}
