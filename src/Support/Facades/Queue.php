<?php 

namespace Nova\Support\Facades;

/**
 * @see \Nova\Queue\QueueManager
 * @see \Nova\Queue\Queue
 */
class Queue extends Facade
{

	/**
	* Get the registered name of the component.
	*
	* @return string
	*/
	protected static function getFacadeAccessor() { return 'queue'; }

}
