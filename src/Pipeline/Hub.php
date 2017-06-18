<?php

namespace Nova\Pipeline;

use Nova\Container\Container;
use Nova\Pipeline\Contracts\HubInterface;

use Closure;


class Hub implements HubInterface
{
	/**
	 * The container implementation.
	 *
	 * @var \Nova\Container\Container
	 */
	protected $container;

	/**
	 * All of the available pipelines.
	 *
	 * @var array
	 */
	protected $pipelines = array();

	/**
	 * Create a new Depot instance.
	 *
	 * @param  \Nova\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Define the default named pipeline.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function defaults(Closure $callback)
	{
		return $this->pipeline('default', $callback);
	}

	/**
	 * Define a new named pipeline.
	 *
	 * @param  string  $name
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function pipeline($name, Closure $callback)
	{
		$this->pipelines[$name] = $callback;
	}

	/**
	 * Send an object through one of the available pipelines.
	 *
	 * @param  mixed  $object
	 * @param  string|null  $pipeline
	 * @return mixed
	 */
	public function pipe($object, $pipeline = null)
	{
		$pipeline = $pipeline ?: 'default';

		return call_user_func(
			$this->pipelines[$pipeline], new Pipeline($this->container), $object
		);
	}
}
