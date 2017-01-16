<?php

namespace Nova\Module\Console\Commands;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Database\Migrations\Migrator;
use Nova\Module\ModuleRepository;
use Nova\Support\Arr;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ModuleMigrateCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database migrations for a specific or all modules';

    /**
     * @var \Nova\Module\ModuleRepository
     */
    protected $module;

    /**
     * @var Migrator
     */
    protected $migrator;

    /**
     * Create a new command instance.
     *
     * @param Migrator $migrator
     * @param ModuleRepository  $module
     */
    public function __construct(Migrator $migrator, ModuleRepository $module)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->module   = $module;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) return;

        $this->prepareDatabase();

        $slug = $this->argument('slug');

        if (! empty($slug)) {
            if (! $this->module->exists($slug)) {
                return $this->error('Module does not exist.');
            }

            if ($this->module->isEnabled($slug)) {
                return $this->migrate($slug);
            } else {
                return $this->error('Nothing to migrate.');
            }
        } else {
            if ($this->option('force')) {
                $modules = $this->module->all();
            } else {
                $modules = $this->module->enabled();
            }

            foreach ($modules as $module) {
                $this->migrate($module['slug']);
            }
        }
    }

    /**
     * Run migrations for the specified module.
     *
     * @param string $slug
     *
     * @return mixed
     */
    protected function migrate($slug)
    {
        if (! $this->module->exists($slug)) {
            return $this->error('Module does not exist.');
        }

        $pretend = Arr::get($this->option(), 'pretend', false);

        $path = $this->getMigrationPath($slug);

        $this->migrator->run($path, $pretend);

        //
        foreach ($this->migrator->getNotes() as $note) {
            if (!$this->option('quiet')) {
                $this->line($note);
            }
        }

        if ($this->option('seed')) {
            $this->call('module:seed', ['slug' => $slug, '--force' => true]);
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
        $path = $this->module->getModulePath($slug);

        return $path .'Database' .DS .'Migrations' .DS;
    }

    /**
     * Prepare the migration database for running.
     */
    protected function prepareDatabase()
    {
        $this->migrator->setConnection($this->option('database'));

        if (!$this->migrator->repositoryExists()) {
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
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
        );
    }
}
