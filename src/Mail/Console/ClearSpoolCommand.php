<?php

namespace Nova\Mail\Console;

use Nova\Console\Command;

use Swift_SpoolTransport;


class ClearSpoolCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'mailer:spool:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Clear the Mailer Spool queue of the messages failed to be sent";

    /**
     * The Swift Spool Transport instance.
     *
     * @var \Swift_SpoolTransport
     */
    protected $transport;


    /**
     * Create a new Spool Clear Command instance.
     *
     * @param  \Swift_SpoolTransport  $transport
     * @return void
     */
    public function __construct(Swift_SpoolTransport $transport)
    {
        parent::__construct();

        $this->transport = $transport;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $spool = $this->transport->getSpool();

        $spool->clear();

        $this->info('Mailer Spool queue cleared!');
    }
}
