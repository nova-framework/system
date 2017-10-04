<?php

namespace Nova\Console\Scheduling;

use Nova\Console\Command;


class ScheduleRunCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'schedule:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the scheduled commands';

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
        $events = $this->schedule->dueEvents($this->container);

        foreach ($events as $event) {
            $this->line('<info>Running scheduled command:</info> ' .$event->getSummaryForDisplay());

            $event->run($this->container);
        }

        if (count($events) === 0) {
            $this->info('No scheduled commands are ready to run.');
        }
    }
}
