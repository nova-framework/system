<?php

namespace Nova\Foundation\Bootstrap;

use Nova\Foundation\Application;


class BootApplication
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$app->registerConfiguredProviders();

		$app->boot();
	}
}
