<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;

use Psy\Shell;
use Psy\Configuration;

use Symfony\Component\Console\Input\InputArgument;


class TinkerCommand extends Command
{
    /**
     * Forge commands to include in the tinker shell.
     *
     * @var array
     */
    protected $commandWhitelist = [
        'clear-compiled', 'env', 'migrate', 'optimize',
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tinker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Interact with your Application";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->getApplication()->setCatchExceptions(false);

        $config = new Configuration();

        $config->getPresenter()->addCasters(
            $this->getCasters()
        );

        $shell = new Shell($config);

        $shell->addCommands($this->getCommands());
        $shell->setIncludes($this->argument('include'));

        $shell->run();
    }

    /**
     * Get artisan commands to pass through to PsySH.
     *
     * @return array
     */
    protected function getCommands()
    {
        $commands = [];

        foreach ($this->getApplication()->all() as $name => $command) {
            if (in_array($name, $this->commandWhitelist)) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    /**
     * Get an array of Nova tailored casters.
     *
     * @return array
     */
    protected function getCasters()
    {
        return array(
            'Nova\Foundation\Application' => 'Nova\Foundation\Console\FrameworkCaster::castApplication',
            'Nova\Support\Collection'     => 'Nova\Foundation\Console\FrameworkCaster::castCollection',
            'Nova\Database\ORM\Model'     => 'Nova\Foundation\Console\FrameworkCaster::castModel',
        );
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('include', InputArgument::IS_ARRAY, 'Include file(s) before starting tinker'),
        );
    }

}
