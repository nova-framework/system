<?php

namespace Nova\Foundation\Console;

use Nova\Console\Contracts\KernelInterface;
use Nova\Console\Scheduling\Schedule;
use Nova\Console\Application as Forge;
use Nova\Events\Dispatcher;
use Nova\Foundation\Application;

use Exception;
use Throwable;


class Kernel implements KernelInterface
{
	/**
	 * The application instance.
	 *
	 * @var \Nova\Foundation\Application
	 */
	protected $app;

	/**
	 * The event dispatcher implementation.
	 *
	 * @var \Nova\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The forge console instance.
	 *
	 * @var  \Nova\Console\Application
	 */
	protected $forge;

	/**
	 * The Forge commands provided by the application.
	 *
	 * @var array
	 */
	protected $commands = array();

	/**
	 * The bootstrap classes for the application.
	 *
	 * @var array
	 */
	protected $bootstrappers = array(
		'Nova\Foundation\Bootstrap\LoadEnvironmentVariables',
		'Nova\Foundation\Bootstrap\LoadConfiguration',
		'Nova\Foundation\Bootstrap\HandleExceptions',
		'Nova\Foundation\Bootstrap\RegisterFacades',
		'Nova\Foundation\Bootstrap\RegisterProviders',
		'Nova\Foundation\Bootstrap\BootProviders',
		'Nova\Foundation\Bootstrap\SetRequestForConsole',
	);

	/**
	 * Create a new forge command runner instance.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return void
	 */
	public function __construct(Application $app, Dispatcher $events)
	{
		if (! defined('FORGE_BINARY')) {
			define('FORGE_BINARY', 'forge');
		}

		$this->app = $app;

		$this->events = $events;

		$this->defineConsoleSchedule();
	}

	/**
	 * Define the application's command schedule.
	 *
	 * @return void
	 */
	protected function defineConsoleSchedule()
	{
		$this->app->instance(
			'Nova\Console\Scheduling\Schedule', $schedule = new Schedule()
		);

		$this->schedule($schedule);
	}

	/**
	 * Run the console application.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @return int
	 */
	public function handle($input, $output = null)
	{
		try {
			$this->bootstrap();

			return $this->getForge()->run($input, $output);
		} catch (Exception $e) {
			$this->reportException($e);

			$this->renderException($output, $e);

			return 1;
		} catch (Throwable $e) {
			$e = new FatalThrowableError($e);

			$this->reportException($e);

			$this->renderException($output, $e);

			return 1;
		}
	}

	/**
	 * Terminate the application.
	 *
	 * @param  \Symfony\Component\Console\Input\InputInterface  $input
	 * @param  int  $status
	 * @return void
	 */
	public function terminate($input, $status)
	{
		$this->app->terminate();
	}

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Nova\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		//
	}

	/**
	 * Run a Forge console command by name.
	 *
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return int
	 */
	public function call($command, array $parameters = array())
	{
		$this->bootstrap();

		return $this->getForge()->call($command, $parameters);
	}

	/**
	 * Get all of the commands registered with the console.
	 *
	 * @return array
	 */
	public function all()
	{
		$this->bootstrap();

		return $this->getForge()->all();
	}

	/**
	 * Get the output for the last run command.
	 *
	 * @return string
	 */
	public function output()
	{
		$this->bootstrap();

		return $this->getForge()->output();
	}

	/**
	 * Get the forge console instance.
	 *
	 * @return \Nova\Console\Application
	 */
	protected function getForge()
	{
		if (isset($this->forge)) {
			return $this->forge;
		}

		$this->forge = $forge = new Forge($this->app, $this->events, $this->app->version());

		$forge->resolveCommands($this->commands);

		return $forge;
	}

	/**
	 * Bootstrap the application for artisan commands.
	 *
	 * @return void
	 */
	public function bootstrap()
	{
		if (! $this->app->hasBeenBootstrapped()) {
			$this->app->bootstrapWith($this->bootstrappers());
		}

		$this->app->loadDeferredProviders();
	}

	/**
	 * Get the bootstrap classes for the application.
	 *
	 * @return array
	 */
	protected function bootstrappers()
	{
		return $this->bootstrappers;
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param  \Exception  $e
	 * @return void
	 */
	protected function reportException(Exception $e)
	{
		$this->getExceptionHandler()->report($e);
	}

	/**
	 * Report the exception to the exception handler.
	 *
	 * @param  \Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  \Exception  $e
	 * @return void
	 */
	protected function renderException($output, Exception $e)
	{
		$this->getExceptionHandler()->renderForConsole($output, $e);
	}

	/**
	 * Get the Nova application instance.
	 *
	 * @return \Nova\Foundation\Contracts\ExceptionHandlerInterface
	 */
	public function getExceptionHandler()
	{
		return $this->app->make('Nova\Foundation\Contracts\ExceptionHandlerInterface');
	}
}
