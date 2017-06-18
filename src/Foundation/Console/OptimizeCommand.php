<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Support\Composer;

use Symfony\Component\Console\Input\InputOption;

use ClassPreloader\Factory;
use ClassPreloader\Exceptions\VisitorExceptionInterface;


class OptimizeCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'optimize';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Optimize the Framework for better performance";

	/**
	 * The composer instance.
	 *
	 * @var \Nova\Foundation\Composer
	 */
	protected $composer;

	/**
	 * Create a new optimize command instance.
	 *
	 * @param  \Nova\Foundation\Composer  $composer
	 * @return void
	 */
	public function __construct(Composer $composer)
	{
		parent::__construct();

		$this->composer = $composer;
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$this->info('Generating optimized class loader');

		if ($this->option('psr')) {
			$this->composer->dumpAutoloads();
		} else {
			$this->composer->dumpOptimized();
		}

		if ($this->option('force') || ! $this->container['config']['app.debug']) {
			$this->info('Compiling common classes');

			$this->compileClasses();
		} else {
			$this->call('clear-compiled');
		}
	}

	/**
	 * Generate the compiled class file.
	 *
	 * @return void
	 */
	protected function compileClasses()
	{
		$outputPath = $this->container['path'] .DS .'Boot' .DS .'Compiled.php';

		//
		$config = array('skip' => true);

		$preloader = with(new Factory)->create($config);

		$handle = $preloader->prepareOutput($outputPath);

		foreach ($this->getClassFiles() as $file) {
			try {
				fwrite($handle, $preloader->getCode($file, false)."\n");
			} catch (VisitorExceptionInterface $e) {
				//
			}
		}

		fclose($handle);
	}

	/**
	 * Get the classes that should be combined and compiled.
	 *
	 * @return array
	 */
	protected function getClassFiles()
	{
		$app = $this->container;

		$core = require __DIR__ .DS .'Optimize' .DS .'config.php';

		return array_merge($core, $this->container['config']['compile']);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('force', null, InputOption::VALUE_NONE, 'Force the compiled class file to be written.'),
			array('psr', null, InputOption::VALUE_NONE, 'Do not optimize Composer dump-autoload.'),
		);
	}

}
