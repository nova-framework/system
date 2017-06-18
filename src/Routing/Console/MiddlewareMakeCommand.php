<?php

namespace Mini\Routing\Console;

use Mini\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Mini\Routing\Generators\MiddlewareGenerator;


class MiddlewareMakeCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:middleware';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a new Middleware class';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Middleware';


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
		return realpath(__DIR__) .str_replace('/', DS, '/stubs/middleware.stub');
	}

	/**
	 * Get the default namespace for the class.
	 *
	 * @param  string  $rootNamespace
	 * @return string
	 */
	protected function getDefaultNamespace($rootNamespace)
	{
		return $rootNamespace .'\Http\Middleware';
	}
}
