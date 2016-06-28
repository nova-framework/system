<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Foundation\Application;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


/**
 * List the Modules
 */
class ModuleListCommand extends AbstractCommand
{
    /**
     * Name of the command
     * @var string
     */
    protected $name = 'modules';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Show all registered Modules.';

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
        $modules = $this->getModules();

        // Return error if no Modules found
        if (empty($modules)) {
            return $this->error("Your application doesn't have any registered modules.");
        }

        // Display the Modules info
        $this->displayModules($modules);
    }

}
