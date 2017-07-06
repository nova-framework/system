<?php

namespace Nova\Foundation\Bootstrap;

use Nova\Foundation\Application;
use Nova\Foundation\AliasLoader;
use Nova\Support\Facades\Facade;


class RegisterFacades
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		Facade::clearResolvedInstances();

		Facade::setFacadeApplication($app);

		//
		$aliases = $app['config']->get('app.aliases', array());

		AliasLoader::getInstance($aliases)->register();
	}
}
