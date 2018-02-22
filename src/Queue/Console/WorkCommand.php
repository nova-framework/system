<?php

namespace Nova\Queue\Console;

use Nova\Queue\Worker;
use Nova\Queue\Jobs\Job;
use Nova\Console\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class WorkCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'queue:work';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the next job on a queue';

    /**
     * The queue worker instance.
     *
     * @var \Nova\Queue\Worker
     */
    protected $worker;

    /**
     * Create a new queue listen command.
     *
     * @param  \Nova\Queue\Worker  $worker
     * @return void
     */
    public function __construct(Worker $worker)
    {
        parent::__construct();

        $this->worker = $worker;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $daemon = $this->option('daemon');

        if ($this->downForMaintenance() && ! $daemon) {
            return;
        }

        // We'll listen to the processed and failed events so we can write information
        // to the console as jobs are processed, which will let the developer watch
        // which jobs are coming through a queue and be informed on its progress.

        $this->listenForEvents();

        $queue = $this->option('queue');
        $delay = $this->option('delay');

        // The memory limit is the amount of memory we will allow the script to occupy
        // before killing it and letting a process manager restart it for us, which
        // is to protect us against any memory leaks that will be in the scripts.
        $memory = $this->option('memory');

        if (empty($connection = $this->argument('connection'))) {
            $connection = $this->container['config']['queue.default'];
        }

        $this->runWorker($connection, $queue, $delay, $memory, $daemon);
    }

    /**
     * Listen for the queue events in order to update the console output.
     *
     * @return void
     */
    protected function listenForEvents()
    {
        $this->container['events']->listen('nova.queue.processed', function ($connection, $job)
        {
            $this->writeOutput($job, false);
        });

        $this->container['events']->listen('nova.queue.failed', function ($connection, $job)
        {
            $this->writeOutput($job, true);
        });
    }

    /**
     * Run the worker instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $delay
     * @param  int  $memory
     * @param  bool  $daemon
     * @return array
     */
    protected function runWorker($connection, $queue, $delay, $memory, $daemon = false)
    {
        $this->worker->setDaemonExceptionHandler(
            $this->container['Nova\Foundation\Contracts\ExceptionHandlerInterface']
        );

        $sleep = $this->option('sleep');
        $tries = $this->option('tries');

        if (! $daemon) {
            return $this->worker->pop($connection, $queue, $delay, $sleep, $tries);
        }

        $this->worker->setCache(
            $this->container['cache']->driver()
        );

        return $this->worker->daemon($connection, $queue, $delay, $memory, $sleep, $tries);
    }

    /**
     * Write the status output for the queue worker.
     *
     * @param  \Nova\Queue\Jobs\Job  $job
     * @param  bool  $failed
     * @return void
     */
    protected function writeOutput(Job $job, $failed)
    {
        if ($failed) {
            $this->output->writeln('<error>Failed:</error> ' .$job->resolveName());
        } else {
            $this->output->writeln('<info>Processed:</info> ' .$job->resolveName());
        }
    }

    /**
     * Determine if the worker should run in maintenance mode.
     *
     * @return bool
     */
    protected function downForMaintenance()
    {
        if ($this->option('force')) {
            return false;
        }

        return $this->container->isDownForMaintenance();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('connection', InputArgument::OPTIONAL, 'The name of connection', null),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('queue',  null, InputOption::VALUE_OPTIONAL, 'The queue to listen on'),
            array('daemon', null, InputOption::VALUE_NONE,     'Run the worker in daemon mode'),
            array('delay',  null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0),
            array('force',  null, InputOption::VALUE_NONE,     'Force the worker to run even in maintenance mode'),
            array('memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128),
            array('sleep',  null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3),
            array('tries',  null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0),
        );
    }

}
