<?php

namespace Nova\Foundation\Support\Providers;

use Nova\Events\Dispatcher;

use Nova\Support\ServiceProvider;


class EventServiceProvider extends ServiceProvider
{
	/**
	 * The event handler mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = array();

	/**
	 * The subscriber classes to register.
	 *
	 * @var array
	 */
	protected $subscribe = array();


	/**
	 * Register the application's event listeners.
	 *
	 * @param  \Nova\Events\Dispatcher  $events
	 * @return void
	 */
	public function boot(Dispatcher $events)
	{
		foreach ($this->listen as $event => $listeners) {
			foreach ($listeners as $listener) {
				$events->listen($event, $listener);
			}
		}

		foreach ($this->subscribe as $subscriber) {
			$events->subscribe($subscriber);
		}
	}

	/**
	 * Load the standard Events file for the application.
	 *
	 * @param  string  $path
	 * @return mixed
	 */
	protected function loadEventsFrom($path)
	{
		$events = $this->app['events'];

		return require $path;
	}

	/**
	 * {@inheritdoc}
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the events and handlers.
	 *
	 * @return array
	 */
	public function listens()
	{
		return $this->listen;
	}
}
