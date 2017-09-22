<?php

namespace Nova\Mail\Console;

use Nova\Console\Command;
use Nova\Events\Dispatcher;

use Swift_Transport;
use Swift_SpoolTransport;


class FlushSpoolCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mailer:spool:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Send the messages queued in the Mailer Spool";

    /**
     * The event dispatcher instance.
     *
     * @var \Nova\Events\Dispatcher
     */
    protected $events;

    /**
     * The Swift Transport instance.
     *
     * @var \Swift_Transport
     */
    protected $transport;

    /**
     * The Swift Spool Transport instance.
     *
     * @var \Swift_SpoolTransport
     */
    protected $spoolTransport;


    /**
     * Create a new Flush Spool Queue Command instance.
     *
     * @return void
     */
    public function __construct(Swift_Transport $transport, Swift_SpoolTransport $spoolTransport, Dispatcher $events = null)
    {
        parent::__construct();

        //
        $this->events = $events;

        $this->transport = $transport;

        $this->spoolTransport = $spoolTransport;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $config = $this->container['config'];

        // Get the Swift Spool instance.
        $spool = $this->getSwiftSpool();

        // Execute a recovery if for any reason a process is sending for too long.
        $timeout = $config->get('mail.spool.timeout', 900);

        if (is_integer($timeout) && ($timeout > 0)) {
            $spool->recover($timeout);
        }

        // Sends messages using the given transport instance.
        $failedRecipients = array();

        $result = $spool->flushQueue($this->transport, $failedRecipients);

        if (isset($this->events)) {
            $this->events->fire('mailer.spool.flushing', array($result, $failedRecipients));
        }

        $this->info(__d('nova', 'Sent {0} email(s) ...', $result));
    }

    /**
     * Get the messages from the Mailer's Spool instance.
     *
     * @return \Swift_Spool
     */
    protected function getSwiftSpool()
    {
        return $this->spoolTransport->getSpool();
    }
}
