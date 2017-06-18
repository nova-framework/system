<?php

namespace Nova\Foundation\Bootstrap;

use Nova\Config\Repository;
use Nova\Foundation\AliasLoader;
use Nova\Foundation\Application;
use Nova\Support\Facades\Facade;


class LoadConfiguration
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		// Register the Config Repository.
		$app->instance('config', $config = new Repository(
			$app->getConfigLoader(), $app->environment()
		));

		// Set the default Timezone.
		date_default_timezone_set($config['app.timezone']);

		// Register the Facades.
		Facade::clearResolvedInstances();

		Facade::setFacadeApplication($app);

		// Register the class aliases.
		AliasLoader::getInstance($config->get('app.aliases'))->register();
	}
}
