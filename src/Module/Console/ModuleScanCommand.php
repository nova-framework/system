<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Foundation\Composer;
use Nova\Foundation\Application;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


/**
* Scan available Modules
*/
class ModuleScanCommand extends AbstractCommand
{
    /**
     * Name of the command
     * @var string
     */
    protected $name = 'modules:scan';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Scan for the Modules and cache the Modules metadata.';

    /**
     * Path to the modules monifest
     * @var string
     */
    protected $manifestPath;

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
        $this->info('Scanning modules');

        $modules = $this->framework['modules'];

        // Get the Table helper.
        $this->table = new Table($this->output);

        // Delete the Manifest.
        $modules->deleteManifest();

        // Run the Modules Scanner.
        $this->modules = $modules->scan();

        // Return error if no modules found
        if (empty($this->modules)) {
            return $this->error("Your application doesn't have any valid Modules.");
        }

        // Also run composer dump-autoload
        $this->dumpAutoload();

        // Display number of found modules
        $this->info('Found ' . count($this->modules) . ' modules:');

        // Display the Modules info
        $this->displayModules($this->getModules());
    }

}
