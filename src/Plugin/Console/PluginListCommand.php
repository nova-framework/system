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
    protected $description = 'List all application plugins';

    /**
     * @var \Nova\Plugin\PluginManager
     */
    protected $plugins;

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['#', 'Package', 'Slug', 'Config Files', 'Translations', 'Is Theme?', 'Location'];

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
        $plugins = $this->plugins->all();

        if (count($plugins) == 0) {
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

        //
        $count = 1;

        foreach ($plugins as $plugin) {
            $results[] = $this->getPluginInformation($plugin, $count);

            $count++;
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
    protected function getPluginInformation($plugin, $count)
    {
        $config   = $plugin['path'] .'Config';
        $language = $plugin['path'] .'Language';

        if ($plugin['location'] === 'local') {
            $location = ($plugin['theme'] === true) ? 'Themes' : 'Plugins';
        } else {
            $location = 'Vendor';
        }

        return array(
            'id'       => $count,
            'name'     => $plugin['name'],
            'slug'     => $plugin['slug'],
            'config'   => is_dir($config)   ? 'Yes' : 'No',
            'language' => is_dir($language) ? 'Yes' : 'No',
            'theme'    => ($plugin['theme'] === true) ? 'Yes' : 'No',
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
