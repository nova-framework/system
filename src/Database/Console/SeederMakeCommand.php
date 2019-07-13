<?php

namespace Nova\Database\Console;

use Nova\Console\GeneratorCommand;
use Nova\Filesystem\Filesystem;
use Nova\Support\Composer;


class SeederMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Database Seeder class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * The Composer instance.
     *
     * @var \Nova\Foundation\Composer
     */
    protected $composer;


    /**
     * Create a new command instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @param  \Nova\Foundation\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct($files);

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        $this->composer->dumpAutoloads();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ .str_replace('/', DS, '/stubs/seeder.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace .'\Database\Seeds';
    }
}
