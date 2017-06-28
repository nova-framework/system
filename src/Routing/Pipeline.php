<?php

namespace Nova\Routing;

use Nova\Http\Request;
use Nova\Foundation\Contracts\ExceptionHandlerInterface as ExceptionHandler;
use Nova\Pipeline\Pipeline as BasePipeline;

use Symfony\Component\Debug\Exception\FatalThrowableError;

use Closure;
use Exception;
use Throwable;


class Pipeline extends BasePipeline
{
	/**
	 * Get the final piece of the Closure onion.
	 *
	 * @param  \Closure  $destination
	 * @return \Closure
	 */
	protected function getInitialSlice(Closure $destination)
	{
		return function ($passable) use ($destination)
		{
			try {
				return call_user_func($destination, $passable);
			}
			catch (Exception $e) {
				return $this->handleException($passable, $e);
			}
			catch (Throwable $e) {
				return $this->handleException($passable, new FatalThrowableError($e));
			}
		};
	}

	/**
	 * Get a Closure that represents a slice of the application onion.
	 *
	 * @param  \Closure  $stack
	 * @param  mixed  $pipe
	 * @return \Closure
	 */
	protected function getSlice($stack, $pipe)
	{
		return function ($passable) use ($stack, $pipe)
		{
			try {
				return $this->callPipe($pipe, $passable, $stack);
			}
			catch (Exception $e) {
				return $this->handleException($passable, $e);
			}
			catch (Throwable $e) {
				return $this->handleException($passable, new FatalThrowableError($e));
			}
		};
	}

	/**
	 * Handle the given exception.
	 *
	 * @param  mixed  $passable
	 * @param  \Exception  $e
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	protected function handleException($passable, Exception $e)
	{
		if ($this->shouldThrowExceptions() || (! $passable instanceof Request)) {
			throw $e;
		}

		$handler = $this->container->make(ExceptionHandler::class);

		$handler->report($e);

		$response = $handler->render($passable, $e);

		if (method_exists($response, 'withException')) {
			$response->withException($e);
		}

		return $response;
	}

	/**
	 * Determines whether exceptions should be throw during execution.
	 *
	 * @return bool
	 */
	protected function shouldThrowExceptions()
	{
		return ! $this->container->bound(ExceptionHandler::class);
	}
}
