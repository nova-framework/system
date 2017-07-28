<?php

namespace Nova\Plugins\Console;

use Nova\Console\Command;
use Nova\Plugins\PluginManager;


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
     * @var \Nova\Plugins\PluginManager
     */
    protected $plugins;

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Package', 'Slug', 'Order', 'Location', 'Status'];

    /**
     * Create a new command instance.
     *
     * @param \Nova\Plugins\PluginManager $plugin
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
        $plugins = $this->plugins->all();

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
        $plugins = $this->plugins->all();

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
        if ($plugin['location'] === 'local') {
            $location = 'Local';
        } else {
            $location = 'Vendor';
        }

        $enabled = $this->plugins->isEnabled($plugin['slug']);

        return array(
            'name'         => $plugin['name'],
            'slug'         => $plugin['slug'],
            'order'        => $plugin['order'],
            'location'    => $location,
            'status'    => $enabled ? 'Enabled' : 'Disabled',
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
