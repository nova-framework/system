<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Foundation\Application;
use Nova\Foundation\Composer;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


/**
* Modules console commands
*/
class ModuleSeedCommand extends AbstractCommand
{
    /**
     * Name of the command
     * @var string
     */
    protected $name = 'modules:seed';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Seed the Database from the Modules.';

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
        $this->info('Seeding database from modules');

        // Get all modules or 1 specific
        if ($moduleName = $this->input->getArgument('module')) {
            $modules = $this->framework['modules']->module($moduleName);
        } else {
            $modules = $this->framework['modules']->modules();
        }

        foreach ($modules as $module) {
            if ($module) {
                if ($module->has('seeder')) {
                    $module->seed();

                    $this->info("Seeded '" . $module->name() . "' module.");
                } else {
                    $this->line("Module <info>'" . $module->name() . "'</info> has no seeds.");
                }
            } else {
                $this->error("Module '" . $moduleName . "' does not exist.");
            }
        }
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::OPTIONAL, 'The name of Module being seeded.'),
        );
    }

}
