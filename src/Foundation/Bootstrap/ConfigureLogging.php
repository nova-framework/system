<?php

namespace Nova\Foundation\Bootstrap;

use Nova\Foundation\Application;
use Nova\Log\Writer;

use Monolog\Logger as Monolog;


class ConfigureLogging
{
	/**
	 * Bootstrap the given application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return void
	 */
	public function bootstrap(Application $app)
	{
		$log = $this->registerLogger($app);

		$this->configureHandlers($app, $log);
	}

	/**
	 * Register the logger instance in the container.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return \Nova\Log\Writer
	 */
	protected function registerLogger(Application $app)
	{
		$app->instance('log', $log = new Writer(
			new Monolog('mini-nova'), $app['events'])
		);

		return $log;
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @param  \Nova\Log\Writer  $log
	 * @return void
	 */
	protected function configureHandlers(Application $app, Writer $log)
	{
		//$method = 'configure' .ucfirst($app['config']['app.log']) .'Handler';
		$method = 'configureSingleHandler';

		call_user_func(array($this, $method), $app, $log);
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @param  \Nova\Log\Writer  $log
	 * @return void
	 */
	protected function configureSingleHandler(Application $app, Writer $log)
	{
		$log->useFiles($app->make('path.storage') .DS .'logs' .DS .'framework.log');
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @param  \Nova\Log\Writer  $log
	 * @return void
	 */
	protected function configureDailyHandler(Application $app, Writer $log)
	{
		$log->useDailyFiles(
			$app->make('path.storage') .DS .'logs' .DS .'framework.log',
			$app->make('config')->get('app.log_max_files', 5)
		);
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @param  \Nova\Log\Writer  $log
	 * @return void
	 */
	protected function configureSyslogHandler(Application $app, Writer $log)
	{
		$log->useSyslog('mini-nova');
	}

	/**
	 * Configure the Monolog handlers for the application.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @param  \Nova\Log\Writer  $log
	 * @return void
	 */
	protected function configureErrorlogHandler(Application $app, Writer $log)
	{
		$log->useErrorLog();
	}
}
