<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Database\Migrations\Migrator;
use Nova\Module\Console\MigrationTrait;
use Nova\Module\ModuleManager;
use Nova\Support\Arr;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleMigrateRollbackCommand extends Command
{
    use ConfirmableTrait;
    use MigrationTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate:rollback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last database migrations for a specific or all modules';

    /**
     * @var \Nova\Module\ModuleManager
     */
    protected $modules;

    /**
     * @var Migrator
     */
    protected $migrator;


    /**
     * Create a new command instance.
     *
     * @param \Nova\Module\ModuleManager $module
     */
    public function __construct(Migrator $migrator, ModuleManager $modules)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->modules  = $modules;
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

        if (! empty($slug)) {
            if (! $this->modules->exists($slug)) {
                return $this->error('Module does not exist.');
            }

            return $this->rollback($slug);
        }

        foreach ($this->modules->all() as $module) {
            $this->comment('Rollback the last migration from Module: ' .$module['name']);

            $this->rollback($module['slug']);
        }
    }

    /**
     * Run the migration rollback for the specified module.
     *
     * @param string $slug
     *
     * @return mixed
     */
    protected function rollback($slug)
    {
        if (! $this->modules->exists($slug)) {
            return $this->error('Module does not exist.');
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
        return array(
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
        );
    }
}
