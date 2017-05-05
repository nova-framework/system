<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Module\ModuleManager;

use Symfony\Component\Console\Input\InputArgument;


class ModuleDisableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable a module';

    /**
     * @var \Nova\Module\ModuleManager
     */
    protected $modules;


    /**
     * Create a new command instance.
     *
     * @param \Nova\Module\ModuleManager $module
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
    public function fire()
    {
        $slug = $this->argument('slug');

        if ($this->modules->isEnabled($slug)) {
            $this->modules->disable($slug);

            $this->info('Module was disabled successfully.');
        } else {
            $this->comment('Module is already disabled.');
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::REQUIRED, 'Module slug.'),
        );
    }
}
