<?php

namespace Nova\Foundation\Providers;

use Nova\Support\AggregateServiceProvider;


class FoundationServiceProvider extends AggregateServiceProvider
{
	/**
	 * The provider class names.
	 *
	 * @var array
	 */
	protected $providers = array(
		'Nova\Foundation\Providers\FormRequestServiceProvider',
	);
}
