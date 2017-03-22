<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Module\ModuleManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ModuleMigrateRefreshCommand extends Command
{
    use ConfirmableTrait;

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
     * @var \Nova\Module\ModuleManager
     */
    protected $module;

    /**
     * Create a new command instance.
     *
     * @param ModuleManager  $module
     */
    public function __construct(ModuleManager $module)
    {
        parent::__construct();

        $this->module = $module;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $slug = $this->argument('slug');

        if (! $this->module->exists($slug)) {
            return $this->error('Module does not exist.');
        }

        $this->call('module:migrate:reset', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
            '--force'    => $this->option('force'),
            '--pretend'  => $this->option('pretend'),
        ));

        $this->call('module:migrate', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
        ));

        if ($this->needsSeeding()) {
            $this->runSeeder($slug, $this->option('database'));
        }

        $this->info('Module has been refreshed.');
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
            array('slug', InputArgument::REQUIRED, 'Module slug.'),
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
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
        );
    }
}
