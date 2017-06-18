<?php

namespace Mini\Routing\Console;

use Mini\Console\GeneratorCommand;

use Symfony\Component\Console\Input\InputOption;


class ControllerMakeCommand extends GeneratorCommand
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:controller';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Controller class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Controller';


	/**
	 * Determine if the class already exists.
	 *
	 * @param  string  $rawName
	 * @return bool
	 */
	protected function alreadyExists($rawName)
	{
		return class_exists($rawName);
	}

	/**
	 * Get the stub file for the generator.
	 *
	 * @return string
	 */
	protected function getStub()
	{
		if ($this->option('plain')) {
			return realpath(__DIR__) .str_replace('/', DS, '/stubs/controller.plain.stub');
		}

		return realpath(__DIR__) .str_replace('/', DS, '/stubs/controller.stub');
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace .'\Controllers';
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('plain', null, InputOption::VALUE_NONE, 'Generate an empty Controller class.'),
		);
	}
}
