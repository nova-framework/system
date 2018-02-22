<?php

namespace Nova\Console;

use Nova\Console\Scheduling\Schedule;
use Nova\Console\Scheduling\ScheduleRunCommand;
use Nova\Console\Scheduling\ScheduleFinishCommand;

use Nova\Support\ServiceProvider;


class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bindShared('schedule', function ($app)
        {
            return new Schedule($app);
        });

        $this->registerScheduleRunCommand();
        $this->registerScheduleFinishCommand();
    }

    /**
     * Register the schedule run command.
     *
     * @return void
     */
    protected function registerScheduleRunCommand()
    {
        $this->app->bindShared('command.schedule.run', function ($app)
        {
            return new ScheduleRunCommand($app['schedule']);
        });

        $this->commands('command.schedule.run');
    }

    /**
     * Register the schedule run command.
     *
     * @return void
     */
    protected function registerScheduleFinishCommand()
    {
        $this->app->bindShared('command.schedule.finish', function ($app)
        {
            return new ScheduleFinishCommand($app['schedule']);
        });

        $this->commands('command.schedule.finish');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'command.schedule.run', 'command.schedule.finish'
        );
    }
}
