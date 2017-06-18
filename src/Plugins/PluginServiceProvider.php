<?php

namespace Nova\Plugins;

use Nova\Plugins\PluginManager;
use Nova\Plugins\Repository;
use Nova\Support\ServiceProvider;


class PluginServiceProvider extends ServiceProvider
{
	/**
	 * @var bool Indicates if loading of the Provider is deferred.
	 */
	protected $defer = false;

	/**
	 * Boot the Service Provider.
	 */
	public function boot()
	{
		$plugins = $this->app['plugins'];

		$plugins->register();
	}

	/**
	 * Register the Service Provider.
	 */
	public function register()
	{
		$this->app->bindShared('plugins', function ($app)
		{
			$repository = new Repository($app['config'], $app['files']);

			return new PluginManager($app, $repository);
		});
	}

	/**
	 * Get the Services provided by the Provider.
	 *
	 * @return string
	 */
	public function provides()
	{
		return array('plugins');
	}

}
