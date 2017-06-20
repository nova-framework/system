<?php

namespace Nova\Log\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;


class ClearCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'log:clear';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Flush the Application logs";

	/**
	 * The File System instance.
	 *
	 * @var \Nova\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Create a new Cache Clear Command instance.
	 *
	 * @param  \Nova\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		parent::__construct();

		$this->files = $files;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$path = $this->container['path.storage'] .DS .'logs' .DS .'framework.log';

		$this->files->delete($path);

		//
		$this->info('The Application logs was cleared!');
	}

}
