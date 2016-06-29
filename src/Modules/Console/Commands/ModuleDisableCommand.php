<?php

namespace Nova\Modules\Console\Commands;

use Nova\Console\Command;
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
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $slug = $this->argument('slug');

        if ($this->framework['modules']->isEnabled($slug)) {
            $this->framework['modules']->disable($slug);

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
