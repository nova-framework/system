<?php

namespace Nova\Support\Facades;

use Nova\Support\Facades\Facade;


/**
 * Class Section
 * @package Nova\Support\Facades
 */
class Section extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'view.section'; }

}
