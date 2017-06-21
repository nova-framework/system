<?php

namespace Nova\Broadcasting;

use Nova\Events\Dispatcher;


class PendingBroadcast
{
	/**
	 * The event dispatcher implementation.
	 *
	 * @var \Nova\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The event instance.
	 *
	 * @var mixed
	 */
	protected $event;

	/**
	 * Create a new pending broadcast instance.
	 *
	 * @param  \Nova\Events\Dispatcher  $events
	 * @param  mixed  $event
	 * @return void
	 */
	public function __construct(Dispatcher $events, $event)
	{
		$this->event  = $event;
		$this->events = $events;
	}

	/**
	 * Handle the object's destruction.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		$this->events->dispatch($this->event);
	}
}
