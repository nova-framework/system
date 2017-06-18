<?php

namespace Nova\Bus;

use Nova\Support\ServiceProvider;


class BusServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('Nova\Bus\Dispatcher', function ($app)
		{
			return new Dispatcher($app, function ($connection = null) use ($app)
			{
				return $app->make('queue')->connection($connection);
			});
		});

		$this->app->alias(
			'Nova\Bus\Dispatcher', 'Nova\Bus\Contracts\DispatcherInterface'
		);

		$this->app->alias(
			'Nova\Bus\Dispatcher', 'Nova\Bus\Contracts\QueueingDispatcherInterface'
		);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [
			'Nova\Bus\Dispatcher',
			'Nova\Bus\Contracts\DispatcherInterface',
			'Nova\Bus\Contracts\QueueingDispatcherInterface',
		];
	}
}
