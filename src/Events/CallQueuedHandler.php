<?php

namespace Nova\Events;

use Nova\Queue\Job;
use Nova\Container\Container;


class CallQueuedHandler
{
	/**
	 * The container instance.
	 *
	 * @var \Nova\Container\Container
	 */
	protected $container;

	/**
	 * Create a new job instance.
	 *
	 * @param  \Nova\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * Handle the queued job.
	 *
	 * @param  \Nova\Queue\Job  $job
	 * @param  array  $data
	 * @return void
	 */
	public function call(Job $job, array $data)
	{
		$handler = $this->setJobInstanceIfNecessary(
			$job, $this->container->make($data['class'])
		);

		call_user_func_array(
			array($handler, $data['method']), unserialize($data['data'])
		);

		if (! $job->isDeletedOrReleased()) {
			$job->delete();
		}
	}

	/**
	 * Set the job instance of the given class if necessary.
	 *
	 * @param  \Nova\Queue\Job  $job
	 * @param  mixed  $instance
	 * @return mixed
	 */
	protected function setJobInstanceIfNecessary(Job $job, $instance)
	{
		$traits = class_uses_recursive(get_class($instance));

		if (in_array('Nova\Queue\InteractsWithQueueTrait', $traits)) {
			$instance->setJob($job);
		}

		return $instance;
	}

	/**
	 * Call the failed method on the job instance.
	 *
	 * @param  array  $data
	 * @return void
	 */
	public function failed(array $data)
	{
		$handler = $this->container->make($data['class']);

		if (method_exists($handler, 'failed')) {
			call_user_func_array(array($handler, 'failed'), unserialize($data['data']));
		}
	}
}
