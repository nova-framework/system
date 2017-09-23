<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Support\Composer;

use Symfony\Component\Console\Input\InputOption;

use ClassPreloader\Factory;
use ClassPreloader\Exceptions\VisitorExceptionInterface;


class OptimizeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Optimize the Framework for better performance";

    /**
     * The composer instance.
     *
     * @var \Nova\Foundation\Composer
     */
    protected $composer;

    /**
     * Create a new optimize command instance.
     *
     * @param  \Nova\Foundation\Composer  $composer
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Generating optimized class loader');

        if ($this->option('psr')) {
            $this->composer->dumpAutoloads();
        } else {
            $this->composer->dumpOptimized();
        }

        $this->call('clear-compiled');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('psr', null, InputOption::VALUE_NONE, 'Do not optimize Composer dump-autoload.'),
        );
    }

}
