<?php

namespace Nova\Console\Scheduling;

use Nova\Foundation\Application;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessUtils;


class Schedule
{
	/**
	 * All of the events on the schedule.
	 *
	 * @var array
	 */
	protected $events = array();


	/**
	 * Add a new callback event to the schedule.
	 *
	 * @param  string  $callback
	 * @param  array   $parameters
	 * @return \Nova\Console\Scheduling\Event
	 */
	public function call($callback, array $parameters = array())
	{
		$this->events[] = $event = new CallbackEvent($callback, $parameters);

		return $event;
	}

	/**
	 * Add a new Artisan command event to the schedule.
	 *
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return \Nova\Console\Scheduling\Event
	 */
	public function command($command, array $parameters = array())
	{
		$binary = ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));

		if (defined('HHVM_VERSION')) {
			$binary .= ' --php';
		}

		if (defined('FORGE_BINARY')) {
			$forge = ProcessUtils::escapeArgument(FORGE_BINARY);
		} else {
			$forge = 'forge';
		}

		return $this->exec("{$binary} {$forge} {$command}", $parameters);
	}

	/**
	 * Add a new command event to the schedule.
	 *
	 * @param  string  $command
	 * @param  array  $parameters
	 * @return \Nova\Console\Scheduling\Event
	 */
	public function exec($command, array $parameters = array())
	{
		if (count($parameters)) {
			$command .= ' ' .$this->compileParameters($parameters);
		}

		$this->events[] = $event = new Event($command);

		return $event;
	}

	/**
	 * Compile parameters for a command.
	 *
	 * @param  array  $parameters
	 * @return string
	 */
	protected function compileParameters(array $parameters)
	{
		return collect($parameters)->map(function ($value, $key)
		{
			if (is_numeric($key)) {
				return $value;
			}

			return $key .'=' .(is_numeric($value) ? $value : ProcessUtils::escapeArgument($value));

		})->implode(' ');
	}

	/**
	 * Get all of the events on the schedule.
	 *
	 * @return array
	 */
	public function events()
	{
		return $this->events;
	}

	/**
	 * Get all of the events on the schedule that are due.
	 *
	 * @param  \Nova\Foundation\Application  $app
	 * @return array
	 */
	public function dueEvents(Application $app)
	{
		return array_filter($this->events, function ($event) use ($app)
		{
			return $event->isDue($app);
		});
	}
}
