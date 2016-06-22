<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Foundation\Composer;

use Symfony\Component\Finder\Finder;


class AutoloadCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'dump-autoload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Regenerate Framework autoload files";

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
        $this->call('optimize');
    }

}
