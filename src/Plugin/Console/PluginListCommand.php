<?php

namespace Nova\Plugin\Console;

use Nova\Console\Command;
use Nova\Plugin\PluginManager;


class PluginListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'plugin:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Framework Plugins';

    /**
     * @var \Nova\Plugin\PluginManager
     */
    protected $plugins;

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Package', 'Slug', 'Config Files', 'Translations', 'Location'];

    /**
     * Create a new command instance.
     *
     * @param \Nova\Plugin\PluginManager $plugin
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
    public function fire()
    {
        $plugins = $this->plugins->all()->where('theme', false);

        if ($plugins->isEmpty()) {
            return $this->error("Your application doesn't have any plugins.");
        }

        $this->displayPlugins($this->getPlugins());
    }

    /**
     * Get all plugins.
     *
     * @return array
     */
    protected function getPlugins()
    {
        $plugins = $this->plugins->all()->where('theme', false);

        $results = array();

        foreach ($plugins as $plugin) {
            $results[] = $this->getPluginInformation($plugin);
        }

        return array_filter($results);
    }

    /**
     * Returns plugin manifest information.
     *
     * @param string $plugin
     *
     * @return array
     */
    protected function getPluginInformation($plugin)
    {
        $config   = $plugin['path'] .'Config';
        $language = $plugin['path'] .'Language';

        if ($plugin['location'] === 'local') {
            $location = 'Local';
        } else {
            $location = 'Vendor';
        }

        return array(
            'name'     => $plugin['name'],
            'slug'     => $plugin['slug'],
            'config'   => is_dir($config)   ? 'Yes' : 'No',
            'language' => is_dir($language) ? 'Yes' : 'No',
            'location' => $location,
        );
    }

    /**
     * Display the plugin information on the console.
     *
     * @param array $plugins
     */
    protected function displayPlugins(array $plugins)
    {
        $this->table($this->headers, $plugins);
    }
}