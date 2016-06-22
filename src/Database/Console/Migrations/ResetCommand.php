<?php

namespace Nova\Database\Console\Migrations;

use Nova\Console\Command;
use Nova\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;


class ResetCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations';

    /**
     * The migrator instance.
     *
     * @var \Nova\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Nova\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->migrator->setConnection($this->input->getOption('database'));

        $pretend = $this->input->getOption('pretend');

        while (true) {
            $count = $this->migrator->rollback($pretend);

            foreach ($this->migrator->getNotes() as $note) {
                $this->output->writeln($note);
            }

            if ($count == 0) break;
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),

            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
        );
    }

}
