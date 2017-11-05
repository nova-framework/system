<?php

namespace Nova\Modules\Console;

use Nova\Console\Command;
use Nova\Database\Migrations\Migrator;
use Nova\Modules\Console\MigrationTrait;
use Nova\Modules\ModuleManager;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleMigrateStatusCommand extends Command
{
    use MigrationTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * @var \Nova\Modules\ModuleManager
     */
    protected $modules;

    /**
     * The migrator instance.
     *
     * @var \Nova\Database\Migrations\Migrator
     */
    protected $migrator;


    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Nova\Database\Migrations\Migrator $migrator
     * @return \Nova\Database\Console\Migrations\StatusCommand
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
     * @return void
     */
    public function handle()
    {
        if (! $this->migrator->repositoryExists()) {
            return $this->error('No migrations found.');
        }

        $slug = $this->argument('slug');

        if (! empty($slug)) {
            if (! $this->modules->exists($slug)) {
                return $this->error('Module does not exist.');
            }

            return $this->status($slug);
        }

        foreach ($this->modules->all() as $module) {
            $this->comment('Migrations Status for Module: ' .$module['name']);

            $this->status($module['slug']);
        }
    }

    protected function status($slug)
    {
        if (! $this->modules->exists($slug)) {
            return $this->error('Module does not exist.');
        }

        $this->requireMigrations($slug);

        //
        $this->migrator->setConnection($this->input->getOption('database'));

        $ran = $this->migrator->getRepository()->getRan();

        //
        $migrations = array();

        foreach ($this->getAllMigrationFiles($slug) as $migration) {
            $migrations[] = in_array($migration, $ran) ? array('<info>Y</info>', $migration) : array('<fg=red>N</fg=red>', $migration);
        }

        if (count($migrations) > 0) {
            $this->table(array('Ran?', 'Migration'), $migrations);
        } else {
            $this->error('No migrations found');

            $this->output->writeln('');
        }
    }

    /**
     * Get all of the migration files.
     *
     * @param  string  $path
     * @return array
     */
    protected function getAllMigrationFiles($slug)
    {
        $path = $this->getMigrationPath($slug);

        return $this->migrator->getMigrationFiles($path);
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
        );
    }
}
