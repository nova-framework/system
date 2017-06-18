<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;


class EnvironmentCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'env';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Display the current Framework Environment";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->line('<info>Current Application Environment:</info> <comment>'.$this->container['env'].'</comment>');
	}

}
