<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Module\ModuleManager;

use Symfony\Component\Console\Input\InputArgument;


class ModuleEnableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:enable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable a module';

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

        if ($this->modules->isDisabled($slug)) {
            $this->modules->enable($slug);

            $this->info('Module was enabled successfully.');
        } else {
            $this->comment('Module is already enabled.');
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
