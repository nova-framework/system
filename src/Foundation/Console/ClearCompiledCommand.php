<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;


class ClearCompiledCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'clear-compiled';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Remove the compiled class file";

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		if (file_exists($path = $this->container['path'] .DS .'Boot' .DS .'Compiled.php')) {
			@unlink($path);
		}

		if (file_exists($path = $this->container['config']['app.manifest'] .DS .'services.php')) {
			@unlink($path);
		}
	}

}
