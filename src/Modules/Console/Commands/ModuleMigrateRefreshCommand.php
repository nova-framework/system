<?php

namespace Nova\Modules\Console\Commands;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ModuleMigrateRefreshCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations for a specific or all modules';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) return;

        $slug = $this->argument('slug');

        $this->call('module:migrate:reset', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
            '--pretend'  => $this->option('pretend'),
        ));

        $this->call('module:migrate', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
        ));

        if ($this->needsSeeding()) {
            $this->runSeeder($slug, $this->option('database'));
        }

        if (isset($slug)) {
            $this->info('Module has been refreshed.');
        } else {
            $this->info('All modules have been refreshed.');
        }
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed');
    }

    /**
     * Run the module seeder command.
     *
     * @param string $database
     */
    protected function runSeeder($slug = null, $database = null)
    {
        $this->call('module:seed', array(
            'slug'       => $slug,
            '--database' => $database,
        ));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::OPTIONAL, 'Module slug.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
        ];
    }
}
