<?php

namespace Nova\Console\Scheduling;

use Nova\Container\Container;
use Nova\Console\Scheduling\MutexInterface as Mutex;
use Nova\Foundation\Application;
use Nova\Mail\Mailer;
use Nova\Support\ProcessUtils;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

use Carbon\Carbon;
use Cron\CronExpression;

use Closure;
use LogicException;


class Event
{
    /**
     * The command string.
     *
     * @var string
     */
    public $command;

    /**
     * The cron expression representing the event's frequency.
     *
     * @var string
     */
    public $expression = '* * * * * *';

    /**
     * The timezone the date should be evaluated on.
     *
     * @var \DateTimeZone|string
     */
    public $timezone;

    /**
     * The user the command should run as.
     *
     * @var string
     */
    public $user;

    /**
     * The list of environments the command should run under.
     *
     * @var array
     */
    public $environments = array();

    /**
     * Indicates if the command should run in maintenance mode.
     *
     * @var bool
     */
    public $evenInMaintenanceMode = false;

    /**
     * Indicates if the command should not overlap itself.
     *
     * @var bool
     */
    public $withoutOverlapping = false;

    /**
     * The amount of time the mutex should be valid.
     *
     * @var int
     */
    public $expiresAt = 1440;

    /**
     * Indicates if the command should run in background.
     *
     * @var bool
     */
    public $runInBackground = false;

    /**
     * The filter callback.
     *
     * @var \Closure
     */
    protected $filter;

    /**
     * The reject callback.
     *
     * @var \Closure
     */
    protected $reject;

    /**
     * The location that output should be sent to.
     *
     * @var string
     */
    public $output = '/dev/null';

    /**
     * Indicates whether output should be appended.
     *
     * @var bool
     */
    protected $shouldAppendOutput = false;

    /**
     * The array of callbacks to be run before the event is started.
     *
     * @var array
     */
    protected $beforeCallbacks = array();

    /**
     * The array of callbacks to be run after the event is finished.
     *
     * @var array
     */
    protected $afterCallbacks = array();

    /**
     * The human readable description of the event.
     *
     * @var string
     */
    public $description;

    /**
     * The mutex implementation.
     *
     * @var \Nova\Console\Scheduling\MutexInterface
     */
    public $mutex;


    /**
     * Create a new event instance.
     *
     * @param  \Nova\Console\Scheduling\MutexInterface  $mutex
     * @param  string  $command
     * @return void
     */
    public function __construct(Mutex $mutex, $command)
    {
        $this->mutex = $mutex;
        $this->command = $command;

        $this->output = $this->getDefaultOutput();
    }

    /**
     * Get the default output depending on the OS.
     *
     * @return string
     */
    protected function getDefaultOutput()
    {
        return windows_os() ? 'NUL' : '/dev/null';
    }

    /**
     * Run the given event.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function run(Container $container)
    {
        if ($this->withoutOverlapping && ! $this->mutex->create($this)) {
            return;
        }

        if ($this->runInBackground) {
            $this->runCommandInBackground($container);
        } else {
            $this->runCommandInForeground($container);
        }
    }

    /**
     * Run the command in the background.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    protected function runCommandInBackground(Container $container)
    {
        $this->callBeforeCallbacks($container);

        $process = new Process($this->buildCommand(), base_path(), null, null, null);

        $process->disableOutput();

        $process->run();
    }

    /**
     * Run the command in the foreground.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    protected function runCommandInForeground(Container $container)
    {
        $this->callBeforeCallbacks($container);

        $process = new Process($this->buildCommand(), base_path(), null, null, null);

        $process->run();

        $this->callAfterCallbacks($container);
    }

    /**
     * Call all of the "before" callbacks for the event.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function callBeforeCallbacks(Container $container)
    {
        foreach ($this->beforeCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Call all of the "after" callbacks for the event.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function callAfterCallbacks(Container $container)
    {
        foreach ($this->afterCallbacks as $callback) {
            $container->call($callback);
        }
    }

    /**
     * Build the command string.
     *
     * @return string
     */
    public function buildCommand()
    {
        $command = $this->compileCommand();

        if (! is_null($this->user) && ! windows_os()) {
            return 'sudo -u ' .$this->user .' -- sh -c \'' .$command .'\'';
        }

        return $command;
    }

    /**
     * Build a command string with mutex.
     *
     * @return string
     */
    protected function compileCommand()
    {
        $output = ProcessUtils::escapeArgument($this->output);

        $redirect = $this->shouldAppendOutput ? ' >> ' : ' > ';

        if (! $this->runInBackground) {
            return $this->command .$redirect .$output .' 2>&1';
        }

        $delimiter = windows_os() ? '&' : ';';

        $phpBinary = ProcessUtils::escapeArgument(
            with(new PhpExecutableFinder)->find(false)
        );

        $forgeBinary = defined('FORGE_BINARY') ? ProcessUtils::escapeArgument(FORGE_BINARY) : 'forge';

        $finished = $phpBinary .' ' .$forgeBinary .' schedule:finish ' . ProcessUtils::escapeArgument($this->mutexName());

        return '(' .$this->command .$redirect .$output .' 2>&1 ' .$delimiter .' ' .$finished .') > '
            . ProcessUtils::escapeArgument($this->getDefaultOutput()) .' 2>&1 &';
    }

    /**
     * Get the mutex path for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
    {
        return storage_path('schedule-' .sha1($this->expression .$this->command));
    }

    /**
     * Determine if the given event should run based on the Cron expression.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return bool
     */
    public function isDue(Application $app)
    {
        if (! $this->runsInMaintenanceMode() && $app->isDownForMaintenance()) {
            return false;
        }

        return $this->expressionPasses() && $this->filtersPass($app) && $this->runsInEnvironment($app->environment());
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    protected function expressionPasses()
    {
        $date = Carbon::now();

        if ($this->timezone) {
            $date->setTimezone($this->timezone);
        }

        return CronExpression::factory($this->expression)->isDue($date->toDateTimeString());
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return bool
     */
    protected function filtersPass(Application $app)
    {
        if (($this->filter && ! $app->call($this->filter)) || ($this->reject && $app->call($this->reject))) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the event runs in the given environment.
     *
     * @param  string  $environment
     * @return bool
     */
    public function runsInEnvironment($environment)
    {
        return empty($this->environments) || in_array($environment, $this->environments);
    }

    /**
     * Determine if the event runs in maintenance mode.
     *
     * @return bool
     */
    public function runsInMaintenanceMode()
    {
        return $this->evenInMaintenanceMode;
    }

    /**
     * The Cron expression representing the event's frequency.
     *
     * @param  string  $expression
     * @return $this
     */
    public function cron($expression)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Schedule the event to run hourly.
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->cron('0 * * * * *');
    }

    /**
     * Schedule the event to run daily.
     *
     * @return $this
     */
    public function daily()
    {
        return $this->cron('0 0 * * * *');
    }

    /**
     * Schedule the command at a given time.
     *
     * @param  string  $time
     * @return $this
     */
    public function at($time)
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc).
     *
     * @param  string  $time
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);

        //
        $hours = (int) $segments[0];

        $minutes = (count($segments) == 2) ? (int) $segments[1] : '0';

        return $this->spliceIntoPosition(2, $hours)->spliceIntoPosition(1, $minutes);
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @param  int  $first
     * @param  int  $second
     * @return $this
     */
    public function twiceDaily($first = 1, $second = 13)
    {
        $hours = $first .',' .$second;

        return $this->spliceIntoPosition(1, 0)->spliceIntoPosition(2, $hours);
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->cron('0 0 * * 0 *');
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->cron('0 0 1 * * *');
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->cron('0 0 1 1 * *');
    }

    /**
     * Schedule the event to run every minute.
     *
     * @return $this
     */
    public function everyMinute()
    {
        return $this->cron('* * * * * *');
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->cron('*/5 * * * * *');
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        return $this->cron('*/10 * * * * *');
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->cron('0,30 * * * * *');
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param  array|mixed  $days
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();

        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @param  \DateTimeZone|string  $timezone
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * State that the command should run in background.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->runInBackground = true;

        return $this;
    }

    /**
     * Set which user the command should run as.
     *
     * @param  string  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Limit the environments the command should run in.
     *
     * @param  array|mixed  $environments
     * @return $this
     */
    public function environments($environments)
    {
        $this->environments = is_array($environments) ? $environments : func_get_args();

        return $this;
    }

    /**
     * State that the command should run even in maintenance mode.
     *
     * @return $this
     */
    public function evenInMaintenanceMode()
    {
        $this->evenInMaintenanceMode = true;

        return $this;
    }

    /**
     * Do not allow the event to overlap each other.
     *
     * @param  int  $expiresAt
     * @return $this
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->then(function ()
        {
            $this->mutex->forget($this);

        })->skip(function ()
        {
            return $this->mutex->exists($this);
        });
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function when(Closure $callback)
    {
        $this->filter = $callback;

        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function skip(Closure $callback)
    {
        $this->reject = $callback;

        return $this;
    }

    /**
     * Send the output of the command to a given location.
     *
     * @param  string  $location
     * @param  bool  $append
     * @return $this
     */
    public function sendOutputTo($location, $append = false)
    {
        $this->output = $location;

        $this->shouldAppendOutput = $append;

        return $this;
    }

    /**
     * Append the output of the command to a given location.
     *
     * @param  string  $location
     * @return $this
     */
    public function appendOutputTo($location)
    {
        return $this->sendOutputTo($location, true);
    }

    /**
     * E-mail the results of the scheduled operation.
     *
     * @param  array|mixed  $addresses
     * @param  bool  $onlyIfOutputExists
     * @return $this
     *
     * @throws \LogicException
     */
    public function emailOutputTo($addresses, $onlyIfOutputExists = false)
    {
        $this->ensureOutputIsBeingCapturedForEmail();

        if (! is_array($addresses)) {
            $addresses = array($addresses);
        }

        return $this->then(function (Mailer $mailer) use ($addresses, $onlyIfOutputExists)
        {
            $this->emailOutput($mailer, $addresses, $onlyIfOutputExists);
        });
    }

    /**
     * E-mail the results of the scheduled operation if it produces output.
     *
     * @param  array|mixed  $addresses
     * @return $this
     *
     * @throws \LogicException
     */
    public function emailWrittenOutputTo($addresses)
    {
        return $this->emailOutputTo($addresses, true);
    }

    /**
     * Ensure that output is being captured for email.
     *
     * @return void
     */
    protected function ensureOutputIsBeingCapturedForEmail()
    {
        if (is_null($this->output) || ($this->output == $this->getDefaultOutput())) {
            $output = storage_path('logs/schedule-' .sha1($this->mutexName()) .'.log');

            $this->sendOutputTo($output);
        }
    }

    /**
     * E-mail the output of the event to the recipients.
     *
     * @param  \Nova\Contracts\Mail\Mailer  $mailer
     * @param  array  $addresses
     * @param  bool  $onlyIfOutputExists
     * @return void
     */
    protected function emailOutput(Mailer $mailer, $addresses, $onlyIfOutputExists = false)
    {
        $text = file_exists($this->output) ? file_get_contents($this->output) : '';

        if ($onlyIfOutputExists && empty($text)) {
            return;
        }

        $mailer->raw($text, function ($message) use ($addresses)
        {
            $message->subject($this->getEmailSubject());

            foreach ($addresses as $address) {
                $message->to($address);
            }
        });
    }

    /**
     * Get the e-mail subject line for output results.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        if (isset($this->description)) {
            return __d('nova', 'Scheduled Job Output ({0})', $this->description);
        }

        return __d('nova', 'Scheduled Job Output');
    }

    /**
     * Register a callback to be called before the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function after(Closure $callback)
    {
        return $this->then($callback);
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     * @return $this
     */
    public function name($description)
    {
        return $this->description($description);
    }

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Splice the given value into the given position of the expression.
     *
     * @param  int  $position
     * @param  string  $value
     * @return $this
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->expression);

        //
        $key = $position - 1;

        $segments[$key] = $value;

        return $this->cron(implode(' ', $segments));
    }

    /**
     * Get the summary of the event for display.
     *
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) {
            return $this->description;
        }

        return $this->buildCommand();
    }

    /**
     * Get the Cron expression for the event.
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Set the mutex implementation to be used.
     *
     * @param  \Nova\Console\Scheduling\MutexInterface  $mutex
     * @return $this
     */
    public function preventOverlapsUsing(Mutex $mutex)
    {
        $this->mutex = $mutex;

        return $this;
    }
}
