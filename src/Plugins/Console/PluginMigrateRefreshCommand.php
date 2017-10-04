<?php

namespace Nova\Plugins\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Plugins\PluginManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class PluginMigrateRefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'plugin:migrate:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations for a specific or all plugins';

    /**
     * @var \Nova\Plugins\PluginManager
     */
    protected $plugins;

    /**
     * Create a new command instance.
     *
     * @param PluginManager  $plugin
     */
    public function __construct(PluginManager $plugins)
    {
        parent::__construct();

        $this->plugins = $plugins;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $slug = $this->argument('slug');

        if (! $this->plugins->exists($slug)) {
            return $this->error('Plugin does not exist.');
        }

        $this->call('plugin:migrate:reset', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
            '--force'    => $this->option('force'),
            '--pretend'  => $this->option('pretend'),
        ));

        $this->call('plugin:migrate', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
        ));

        if ($this->needsSeeding()) {
            $this->runSeeder($slug, $this->option('database'));
        }

        $this->info('Plugin has been refreshed.');
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
     * Run the plugin seeder command.
     *
     * @param string $database
     */
    protected function runSeeder($slug = null, $database = null)
    {
        $this->call('plugin:seed', array(
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
            array('slug', InputArgument::REQUIRED, 'Plugin slug.'),
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
