<?php

namespace Nova\Foundation\Bus;

use ArrayAccess;


trait DispatchesJobsTrait
{
	/**
	 * Dispatch a job to its appropriate handler.
	 *
	 * @param  mixed  $job
	 * @return mixed
	 */
	protected function dispatch($job)
	{
		return app('Nova\Bus\Contracts\DispatcherInterface')->dispatch($job);
	}

	/**
	 * Marshal a job and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $job
	 * @param  array  $array
	 * @return mixed
	 */
	protected function dispatchFromArray($job, array $array)
	{
		return app('Nova\Bus\Contracts\DispatcherInterface')->dispatchFromArray($job, $array);
	}

	/**
	 * Marshal a job and dispatch it to its appropriate handler.
	 *
	 * @param  mixed  $job
	 * @param  \ArrayAccess  $source
	 * @param  array  $extras
	 * @return mixed
	 */
	protected function dispatchFrom($job, ArrayAccess $source, $extras = array())
	{
		return app('Nova\Bus\Contracts\DispatcherInterface')->dispatchFrom($job, $source, $extras);
	}
}
