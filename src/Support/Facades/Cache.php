<?php

namespace Nova\Support\Facades;

use Nova\Support\Facades\Facade;


/**
 * @see \Nova\Cache\CacheManager
 * @see \Nova\Cache\Repository
 */
class Cache extends Facade
{
	/**
	 * Return the Application instance.
	 *
	 * @return \Nova\Cache\CacheManager
	 */
	public static function instance()
	{
		$accessor = static::getFacadeAccessor();

		return static::resolveFacadeInstance($accessor);
	}

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'cache'; }

}
