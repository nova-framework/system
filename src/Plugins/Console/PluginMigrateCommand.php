<?php

namespace Nova\Plugins\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Database\Migrations\Migrator;
use Nova\Plugins\PluginManager;
use Nova\Support\Arr;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class PluginMigrateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'plugin:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations for a specific or all plugins';

    /**
     * @var \Nova\Plugins\PluginManager
     */
    protected $plugins;

    /**
     * @var Migrator
     */
    protected $migrator;


    /**
     * Create a new command instance.
     *
     * @param Migrator $migrator
     * @param PluginManager  $plugin
     */
    public function __construct(Migrator $migrator, PluginManager $plugins)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->plugins   = $plugins;
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

        $this->prepareDatabase();

        $slug = $this->argument('slug');

        if (! empty($slug)) {
            if (! $this->plugins->exists($slug)) {
                return $this->error('Plugin does not exist.');
            }

            if ($this->plugins->isEnabled($slug)) {
                return $this->migrate($slug);
            }

            return $this->error('Nothing to migrate.');
        }

        if ($this->option('force')) {
            $plugins = $this->plugins->all();
        } else {
            $plugins = $this->plugins->enabled();
        }

        foreach ($plugins as $plugin) {
            $this->comment('Migrating the Plugin: ' .$plugin['name']);

            $this->migrate($plugin['slug']);
        }
    }

    /**
     * Run migrations for the specified plugin.
     *
     * @param string $slug
     *
     * @return mixed
     */
    protected function migrate($slug)
    {
        if (! $this->plugins->exists($slug)) {
            return $this->error('Plugin does not exist.');
        }

        $path = $this->getMigrationPath($slug);

        //
        $pretend = $this->input->getOption('pretend');

        $this->migrator->run($path, $pretend, $slug);

        //
        foreach ($this->migrator->getNotes() as $note) {
            if (! $this->option('quiet')) {
                $this->line($note);
            }
        }

        if ($this->option('seed')) {
            $this->call('plugin:seed', array('slug' => $slug, '--force' => true));
        }
    }

    /**
     * Get migration directory path.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function getMigrationPath($slug)
    {
        $path = $this->plugins->getPluginPath($slug);

        return $path .'src' .DS .'Database' .DS .'Migrations' .DS;
    }

    /**
     * Prepare the migration database for running.
     */
    protected function prepareDatabase()
    {
        $this->migrator->setConnection($this->option('database'));

        if (! $this->migrator->repositoryExists()) {
            $options = array('--database' => $this->option('database'));

            $this->call('migrate:install', $options);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::OPTIONAL, 'Plugin slug.'),
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
