<?php

namespace Nova\Foundation\Providers;

use Nova\Support\AggregateServiceProvider;


class ConsoleSupportServiceProvider extends AggregateServiceProvider
{
    /**
     * The Provider Class names.
     *
     * @var array
     */
    protected $providers = array(
        'Nova\Console\Scheduling\ScheduleServiceProvider',
        'Nova\Foundation\Providers\ComposerServiceProvider',
        'Nova\Foundation\Providers\PublisherServiceProvider',
        'Nova\Queue\ConsoleServiceProvider',
    );
}
