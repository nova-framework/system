<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Module\ModuleManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ModuleSeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records for a specific or all modules';

    /**
     * @var \Nova\Module\ModuleManager
     */
    protected $modules;

    /**
     * Create a new command instance.
     *
     * @param \Nova\Module\ModuleManager $modules
     */
    public function __construct(ModuleManager $modules)
    {
        parent::__construct();

        $this->modules = $modules;
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
            if (! $this->modules->exists($slug)) {
                return $this->error('Module does not exist.');
            }

            if ($this->modules->isEnabled($slug)) {
                $this->seed($slug);
            } else if ($this->option('force')) {
                $this->seed($slug);
            }

            return;
        }

        if ($this->option('force')) {
            $modules = $this->modules->all();
        } else {
            $modules = $this->modules->enabled();
        }

        foreach ($modules as $module) {
            $slug = $module['slug'];

            $this->seed($slug);
        }
    }

    /**
     * Seed the specific module.
     *
     * @param string $module
     *
     * @return array
     */
    protected function seed($slug)
    {
        $namespace = $this->modules->getNamespace();

        $module = $this->modules->where('slug', $slug);

        // Calculate the Seeder class name.
        $className = $namespace .'\\' .$module['namespace'] .'\Database\Seeds\DatabaseSeeder';

        if (! class_exists($className)) {
            return;
        }

        // Prepare the call parameters.
        $params = array();

        if ($this->option('class')) {
            $params['--class'] = $this->option('class');
        } else {
            $params['--class'] = $className;
        }

        if ($option = $this->option('database')) {
            $params['--database'] = $option;
        }

        if ($option = $this->option('force')) {
            $params['--force'] = $option;
        }

        $this->call('db:seed', $params);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::OPTIONAL, 'Module slug.')
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
            array('class',    null, InputOption::VALUE_OPTIONAL, 'The class name of the module\'s root seeder.'),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.'),
            array('force',    null, InputOption::VALUE_NONE,     'Force the operation to run while in production.'),
        );
    }
}
