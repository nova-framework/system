<?php

namespace Nova\Routing\Generators;

use Nova\Filesystem\Filesystem;


class MiddlewareGenerator
{
	/**
	 * The filesystem instance.
	 *
	 * @var \Nova\Filesystem\Filesystem
	 */
	protected $files;


	/**
	 * Create a new middleware generator instance.
	 *
	 * @param  \Nova\Filesystem\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Create a new middleware file.
	 *
	 * @param  string  $middleware
	 * @param  string  $path
	 * @return void
	 */
	public function make($middleware, $path)
	{
		$stub = $this->getMiddleware($middleware);

		$this->writeFile($stub, $middleware, $path);

		return false;
	}

	/**
	 * Write the completed stub to disk.
	 *
	 * @param  string  $stub
	 * @param  string  $middleware
	 * @param  string  $path
	 * @return void
	 */
	protected function writeFile($stub, $middleware, $path)
	{
		if (str_contains($middleware, '\\')) {
			$this->makeDirectory($middleware, $path);
		}

		$middleware = str_replace('\\', DS, $middleware);

		if (! $this->files->exists($fullPath = $path .DS .$middleware .'.php')) {
			return $this->files->put($fullPath, $stub);
		}
	}

	/**
	 * Create the directory for the middleware.
	 *
	 * @param  string  $middleware
	 * @param  string  $path
	 * @return void
	 */
	protected function makeDirectory($middleware, $path)
	{
		$directory = $this->getDirectory($middleware);

		if (! $this->files->isDirectory($full = $path .DS .$directory)) {
			$this->files->makeDirectory($full, 0777, true);
		}
	}

	/**
	 * Get the directory the middleware should live in.
	 *
	 * @param  string  $middleware
	 * @return string
	 */
	protected function getDirectory($middleware)
	{
		return implode(DS, array_slice(explode('\\', $middleware), 0, -1));
	}

	/**
	 * Get the middleware class stub.
	 *
	 * @param  string  $middleware
	 * @return string
	 */
	protected function getMiddleware($middleware)
	{
		$stub = $this->files->get(__DIR__ .DS .'stubs' .DS .'middleware.stub');

		$segments = explode('\\', $middleware);

		$stub = $this->replaceNamespace($segments, $stub);

		return str_replace('{{class}}', last($segments), $stub);
	}

	/**
	 * Replace the namespace on the middleware.
	 *
	 * @param  array   $segments
	 * @param  string  $stub
	 * @return string
	 */
	protected function replaceNamespace(array $segments, $stub)
	{
		if (count($segments) > 1) {
			$namespace = implode('\\', array_slice($segments, 0, -1));

			return str_replace('{{namespace}}', 'namespace App\Http\Middleware\\' .$namespace .';', $stub);
		} else {
			return str_replace('{{namespace}}', 'namespace App\Http\Middleware;', $stub);
		}
	}

}
