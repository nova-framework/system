<?php

namespace Nova\Module\Console\Commands;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Helpers\Inflector;
use Nova\Filesystem\Filesystem;
use Nova\Database\Migrations\Migrator;
use Nova\Module\ModuleRepository;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleMigrateResetCommand extends Command
{
    use ConfirmableTrait;

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
     * @var \Nova\Module\ModuleRepository
     */
    protected $module;

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
     * @param Modules    $module
     * @param Filesystem $files
     * @param Migrator   $migrator
     */
    public function __construct(ModuleRepository $module, Filesystem $files, Migrator $migrator)
    {
        parent::__construct();

        $this->module   = $module;
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
        if (! $this->confirmToProceed()) return;

        $slug = $this->argument('slug');

        if (! empty($slug)) {
            if ($this->module->isEnabled($slug)) {
                return $this->reset($slug);
            }
        } else {
            $modules = $this->module->enabled()->reverse();

            foreach ($modules as $module) {
                $this->reset($module['slug']);
            }
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
        $this->migrator->setconnection($this->input->getOption('database'));

        $pretend = $this->input->getOption('pretend');

        $migrationPath = $this->getMigrationPath($slug);

        $migrations = array_reverse($this->migrator->getMigrationFiles($migrationPath));

        if (empty($migrations)) {
            return $this->error('Nothing to rollback.');
        }

        foreach ($migrations as $migration) {
            $this->info('Migration: '.$migration);

            $this->runDown($slug, $migration, $pretend);
        }
    }

    /**
     * Run "down" a migration instance.
     *
     * @param string $slug
     * @param object $migration
     * @param bool   $pretend
     */
    protected function runDown($slug, $migration, $pretend)
    {
        $migrationPath = $this->getMigrationPath($slug);

        $file = (string) $migrationPath .DS .$migration .'.php';

        $classFile = implode('_', array_slice(explode('_', basename($file, '.php')), 4));

        $className = Inflector::classify($classFile);

        $table = $this->nova['config']['database.migrations'];

        //
        include $file;

        $instance = new $className();

        $instance->down();

        $this->nova['db']->table($table)
            ->where('migration', $migration)
            ->delete();
    }

    /**
     * Get the console command parameters.
     *
     * @param string $slug
     *
     * @return array
     */
    protected function getParameters($slug)
    {
        $params = array();

        $params['--path'] = $this->getMigrationPath($slug);

        if ($option = $this->option('database')) {
            $params['--database'] = $option;
        }

        if ($option = $this->option('pretend')) {
            $params['--pretend'] = $option;
        }

        if ($option = $this->option('seed')) {
            $params['--seed'] = $option;
        }

        return $params;
    }

    /**
     * Get migrations path.
     *
     * @return string
     */
    protected function getMigrationPath($slug)
    {
        return $this->module->getModulePath($slug) .'Database' .DS .'Migrations';
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
            array('pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run.'),
            array('seed', null, InputOption::VALUE_OPTIONAL, 'Indicates if the seed task should be re-run.'),
        );
    }
}
