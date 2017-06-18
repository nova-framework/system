<?php

namespace Nova\Language;

use Nova\Language\LanguageManager;
use Nova\Support\ServiceProvider;


class LanguageServiceProvider extends ServiceProvider
{

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		//
	}

	/**
	 * Register the Service Provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('language', function($app)
		{
			return new LanguageManager($app, $app['config']['app.locale']);
		});
	}

}
