<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Foundation\Application;
use Nova\Foundation\Composer;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


abstract class AbstractCommand extends Command
{
    /**
     * List of all available modules
     *
     * @var array
     */
    protected $modules;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }

    /**
     * Reformats the modules list for table display
     *
     * @return array
     */
    public function getModules()
    {
        $basePath = $this->framework['path.base'];

        $modules = $this->framework['modules'];

        //
        $results = array();

        foreach($modules->modules() as $name => $module) {
            $path = str_replace($basePath, '', $module->path());

            $results[] = array(
                'name'    => $name,
                'path'    => $path,
                'order'   => $module->order,
                'enabled' => $module->enabled() ? 'true' : '',
            );
        }

        return array_filter($results);
    }


    /**
     * Return a given module
     *
     * @param $module_name
     * @return mixed
     */
    public function getModule($moduleName)
    {
        foreach ($this->getModules() as $module) {
            if ($module['name'] == $moduleName) {
                return $module;
            }
        }

        return false;
    }

    /**
     * Display a module info table in the console
     *
     * @param  array $modules
     * @return void
     */
    public function displayModules($modules)
    {
        // Get the Table helper
        $this->table = new Table($this->output);

        $headers = array('Name', 'Path', 'Order', 'Enabled');

        $this->table->setHeaders($headers)->setRows($modules);

        $this->table->render($this->getOutput());
    }

    /**
     * Dump autoload classes
     *
     * @return void
     */
    public function dumpAutoload()
    {
        // Also run composer dump-autoload
        $composer = new Composer($this->framework['files']);

        $this->info('Generating optimized class loader');

        $composer->dumpOptimized();
    }
}
