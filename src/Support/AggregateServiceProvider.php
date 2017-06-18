<?php

namespace Nova\Support;


class AggregateServiceProvider extends ServiceProvider
{
	/**
	 * The provider class names.
	 *
	 * @var array
	 */
	protected $providers = array();

	/**
	 * An array of the service provider instances.
	 *
	 * @var array
	 */
	protected $instances = array();

	
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->instances = array();

		foreach ($this->providers as $provider) {
			$this->instances[] = $this->app->register($provider);
		}
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		$provides = array();

		foreach ($this->providers as $provider) {
			$instance = $this->app->resolveProviderClass($provider);

			$provides = array_merge($provides, $instance->provides());
		}

		return $provides;
	}
}
