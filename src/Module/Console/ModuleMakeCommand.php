<?php

namespace Nova\Module\Commands;

use Nova\Console\Command;
use Nova\Foundation\Application;
use Nova\Foundation\Composer;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Command for creating a new Module
 */
class ModuleMakeCommand extends AbstractCommand
{
    /**
     * Name of the command
     * @var string
     */
    protected $name = 'modules:create';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Create a new Module.';

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
        $modules = $this->framework['modules'];

        $files = $this->framework['files'];

        // Name of new module
        $moduleName = $this->input->getArgument('module');

        $this->info('Creating module "' .$moduleName .'"');

        // Chech if module exists
        if (! $modules->module($moduleName)) {
            // Get path to modules
            $modulePath = $this->framework['config']['modules.path'];

            if (is_array($modulePath)) $modulePath = array_shift($modulePath);

            $modulePath .= DS .$moduleName;

            // Create the directory
            if (! $files->exists($modulePath)) {
                $files->makeDirectory($modulePath, 0755, true);
            }

            // Create definition and write to file
            $definition = $modules->prettyJsonEncode(array('enabled' => true));

            $files->put($modulePath .DS .'module.json', $definition);

            // Create routes and write to file
            $routes = '<?php' . PHP_EOL;

            $files->put($modulePath .DS .'Routes.php', $routes);

            // Create some resource directories
            $files->makeDirectory($modulePath .DS .'Assets', 0755);
            $files->makeDirectory($modulePath .DS .'Controllers', 0755);
            $files->makeDirectory($modulePath .DS .'Language', 0755);
            $files->makeDirectory($modulePath .DS .'Models', 0755);
            $files->makeDirectory($modulePath .DS .'Migrations', 0755);
            $files->makeDirectory($modulePath .DS .'Seeds', 0755);
            $files->makeDirectory($modulePath .DS .'Views', 0755);

            // Autoload classes
            $this->dumpAutoload();
        } else {
            $this->error('Module with name "' .$moduleName .'" already exists.');
        }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::REQUIRED, 'The name of Module being created.'),
        );
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }

}
