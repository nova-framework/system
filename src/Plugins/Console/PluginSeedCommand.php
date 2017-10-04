<?php

namespace Nova\Plugins\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Plugins\PluginManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class PluginSeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'plugin:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records for a specific or all plugins';

    /**
     * @var \Nova\Plugins\PluginManager
     */
    protected $plugins;

    /**
     * Create a new command instance.
     *
     * @param \Nova\Plugins\PluginManager $plugins
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

        if (! empty($slug)) {
            if (! $this->plugins->exists($slug)) {
                return $this->error('Plugin does not exist.');
            }

            if ($this->plugins->isEnabled($slug)) {
                $this->seed($slug);
            } else if ($this->option('force')) {
                $this->seed($slug);
            }

            return;
        }

        if ($this->option('force')) {
            $plugins = $this->plugins->all();
        } else {
            $plugins = $this->plugins->enabled();
        }

        foreach ($plugins as $plugin) {
            $slug = $plugin['slug'];

            $this->seed($slug);
        }
    }

    /**
     * Seed the specific plugin.
     *
     * @param string $plugin
     *
     * @return array
     */
    protected function seed($slug)
    {
        $plugin = $this->plugins->where('slug', $slug);

        $className = $plugin['namespace'] .'\Database\Seeds\DatabaseSeeder';

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
            array('slug', InputArgument::OPTIONAL, 'Plugin slug.')
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
            array('class', null, InputOption::VALUE_OPTIONAL, 'The class name of the plugin\'s root seeder.'),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
        );
    }
}
