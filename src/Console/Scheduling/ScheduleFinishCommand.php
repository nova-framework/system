<?php

namespace Nova\Console\Scheduling;

use Nova\Console\Command;

use Symfony\Component\Console\Input\InputArgument;


class ScheduleFinishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:finish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handle the completion of a scheduled command';

    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * The schedule instance.
     *
     * @var \Nova\Console\Scheduling\Schedule
     */
    protected $schedule;

    /**
     * Create a new command instance.
     *
     * @param  \Nova\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        collect($this->schedule->events())->filter(function ($value)
        {
            return ($value->mutexName() == $this->argument('id'));

        })->each->callAfterCallbacks($this->container);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('id', InputArgument::REQUIRED, 'The schedule ID'),
        );
    }
}
