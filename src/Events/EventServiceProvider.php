<?php

namespace Nova\Events;

use Nova\Support\ServiceProvider;


class EventServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['events'] = $this->app->share(function($app)
		{
			return with(new Dispatcher($app))->setQueueResolver(function () use ($app)
			{
				return $app['queue'];
			});
		});
	}

}
