<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Filesystem\Filesystem;
use Nova\Database\Migrations\Migrator;
use Nova\Module\Console\MigrationTrait;
use Nova\Module\ModuleManager;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleMigrateResetCommand extends Command
{
    use ConfirmableTrait;
    use MigrationTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations for a specific or all modules';

    /**
     * @var \Nova\Modules\ModuleManager
     */
    protected $modules;

    /**
     * @var Migrator
     */
    protected $migrator;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param ModuleManager  $modules
     * @param Filesystem     $files
     * @param Migrator       $migrator
     */
    public function __construct(ModuleManager $modules, Filesystem $files, Migrator $migrator)
    {
        parent::__construct();

        $this->modules  = $modules;
        $this->files    = $files;
        $this->migrator = $migrator;
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

            if ($this->modules->isEnabled($slug)) {
                return $this->reset($slug);
            }

            return;
        }

        $modules = $this->modules->enabled()->reverse();

        foreach ($modules as $module) {
            $this->comment('Resetting the migrations of Module: ' .$module['name']);

            $this->reset($module['slug']);
        }
    }

    /**
     * Run the migration reset for the specified module.
     *
     * Migrations should be reset in the reverse order that they were
     * migrated up as. This ensures the database is properly reversed
     * without conflict.
     *
     * @param string $slug
     *
     * @return mixed
     */
    protected function reset($slug)
    {
        if (! $this->modules->exists($slug)) {
            return $this->error('Module does not exist.');
        }

        $this->requireMigrations($slug);

        //
        $this->migrator->setconnection($this->input->getOption('database'));

        $pretend = $this->input->getOption('pretend');

        while (true) {
            $count = $this->migrator->rollback($pretend, $slug);

            foreach ($this->migrator->getNotes() as $note) {
                $this->output->writeln($note);
            }

            if ($count == 0) break;
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
            array('pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run.'),
        );
    }
}
