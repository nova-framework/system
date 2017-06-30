<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Support\ServiceProvider;

use Symfony\Component\Console\Input\InputArgument;


class VendorPublishCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'vendor:publish';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Publish any publishable assets from vendor packages";

	/**
	 * The asset publisher instance.
	 *
	 * @var \Nova\Filesystem\Filesystem
	 */
	protected $files;


	/**
	 * Create a new vendor publish command instance.
	 *
	 * @param  \Nova\Foundation\VendorPublisher  $publisher
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
		$group = $this->input->getArgument('group');

		$this->publishGroup($group);
	}

	/**
	 * Publish the assets for a given package name.
	 *
	 * @param  string  $package
	 * @return void
	 */
	protected function publishGroup($group)
	{
		$paths = ServiceProvider::pathsToPublish($group);

		if (empty($paths)) {
			if (is_null($group)) {
				return $this->comment("Nothing to publish.");
			}

			return $this->comment("Nothing to publish for group [{$group}].");
		}

		foreach ($paths as $from => $to) {
			if ($this->files->isFile($from)) {
				$this->publishFile($from, $to);
			} else if ($this->files->isDirectory($from)) {
				$this->publishDirectory($from, $to);
			} else {
				$this->error("Can't locate path: <{$from}>");
			}
		}

		if (is_null($group)) {
			return $this->info("Publishing complete!");
		}

		$this->info("Publishing complete for group [{$group}]!");
	}

	/**
	 * Publish the file to the given path.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return void
	 */
	protected function publishFile($from, $to)
	{
		$this->createParentDirectory(dirname($to));

		$this->files->copy($from, $to);

		$this->status($from, $to, 'File');
	}

	/**
	 * Publish the directory to the given directory.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return void
	 */
	protected function publishDirectory($from, $to)
	{
		$this->createParentDirectory(dirname($to));

		$this->files->copyDirectory($from, $to);

		$this->status($from, $to, 'Directory');
	}

	/**
	 * Create the directory to house the published files if needed.
	 *
	 * @param  string  $directory
	 * @return void
	 */
	protected function createParentDirectory($directory)
	{
		if (! $this->files->isDirectory($directory)) {
			$this->files->makeDirectory($directory, 0755, true);
		}
	}

	/**
	 * Write a status message to the console.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @param  string  $type
	 * @return void
	 */
	protected function status($from, $to, $type)
	{
		$from = str_replace(base_path(), '', realpath($from));

		$to = str_replace(base_path(), '', realpath($to));

		$this->output->writeln('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('group', InputArgument::OPTIONAL, 'The name of assets group being published.'),
		);
	}
}
