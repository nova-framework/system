<?php 

namespace Nova\Support\Facades;

/**
 * @see \Nova\Remote\RemoteManager
 * @see \Nova\Remote\Connection
 */
class SSH extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'remote'; }

}
