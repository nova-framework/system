<?php

namespace Nova\Plugins\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Database\Migrations\Migrator;
use Nova\Plugins\Console\MigrationTrait;
use Nova\Plugins\PluginManager;
use Nova\Support\Arr;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class PluginMigrateRollbackCommand extends Command
{
    use ConfirmableTrait;
    use MigrationTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'plugin:migrate:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migrations for a specific or all plugins';

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
     * @param \Nova\Plugins\PluginManager $plugins
     */
    public function __construct(Migrator $migrator, PluginManager $plugins)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->plugins  = $plugins;
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

        if (! empty($slug)) {
            if (! $this->plugins->exists($slug)) {
                return $this->error('Plugin does not exist.');
            }

            return $this->rollback($slug);
        }

        foreach ($this->plugins->all() as $plugin) {
            $this->comment('Rollback the last migration from Plugin: ' .$plugin['name']);

            $this->rollback($plugin['slug']);
        }
    }

    /**
     * Run the migration rollback for the specified plugin.
     *
     * @param string $slug
     *
     * @return mixed
     */
    protected function rollback($slug)
    {
        if (! $this->plugins->exists($slug)) {
            return $this->error('Plugin does not exist.');
        }

        $this->requireMigrations($slug);

        //
        $this->migrator->setConnection($this->input->getOption('database'));

        $pretend = $this->input->getOption('pretend');

        $this->migrator->rollback($pretend, $slug);

        //
        foreach ($this->migrator->getNotes() as $note) {
            if (! $this->option('quiet')) {
                $this->line($note);
            }
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
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
        );
    }
}
