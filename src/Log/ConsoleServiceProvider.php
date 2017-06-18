<?php

namespace Nova\Log;

use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
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
		$this->app->bindShared('command.log.clear', function($app)
		{
			return new Console\ClearCommand($app['files']);
		});

		$this->commands('command.log.clear');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('command.log.clear');
	}

}
