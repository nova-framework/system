<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class MakePolicyCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Policy class';


    /**
     * Create a new command creator command.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $path = $this->getPath();

        $stub = $this->files->get(__DIR__ .DS .'stubs' .DS .'policy.stub');

        //
        $file = $path .DS .$this->input->getArgument('name').'.php';

        $this->writeCommand($file, $stub);
    }

    /**
     * Write the finished command file to disk.
     *
     * @param  string  $file
     * @param  string  $stub
     * @return void
     */
    protected function writeCommand($file, $stub)
    {
        if (! $this->files->exists($file)) {
            $this->files->put($file, $this->formatStub($stub));

            $this->info('Policy created successfully.');
        } else {
            $this->error('Policy already exists!');
        }
    }

    /**
     * Format the command class stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function formatStub($stub)
    {
        $stub = str_replace('{{class}}', $this->input->getArgument('name'), $stub);

        return $this->addNamespace($stub);
    }

    /**
     * Add the proper namespace to the command.
     *
     * @param  string  $stub
     * @return string
     */
    protected function addNamespace($stub)
    {
        if (! is_null($namespace = $this->input->getOption('namespace'))) {
            return str_replace('{{namespace}}', 'namespace App\Policies\\' .$namespace .';', $stub);
        } else {
            return str_replace('{{namespace}}', 'namespace App\Policies;', $stub);
        }
    }

    /**
     * Get the path where the command should be stored.
     *
     * @return string
     */
    protected function getPath()
    {
        $path = $this->input->getOption('path');

        if (is_null($path)) {
            return $this->nova['path'] .DS .'Policies';
        } else {
            return $this->nova['path.base'] .DS .$path;
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
            array('name', InputArgument::REQUIRED, 'The name of the Policy.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('path', null, InputOption::VALUE_OPTIONAL, 'The path where the Policy should be stored.', null),
            array('namespace', null, InputOption::VALUE_OPTIONAL, 'The Policy namespace.', null),
        );
    }
}
