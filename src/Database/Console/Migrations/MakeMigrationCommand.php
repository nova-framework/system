<?php

namespace Nova\Database\Console\Migrations;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Nova\Database\Migrations\MigrationCreator;


class MakeMigrationCommand extends BaseCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Migration file';

    /**
     * The migration creator instance.
     *
     * @var \Nova\Database\Migrations\MigrationCreator
     */
    protected $creator;

    /**
     * The path to the packages directory (vendor).
     *
     * @var string
     */
    protected $packagePath;

    /**
     * Create a new migration install command instance.
     *
     * @param  \Nova\Database\Migrations\MigrationCreator  $creator
     * @param  string  $packagePath
     * @return void
     */
    public function __construct(MigrationCreator $creator, $packagePath)
    {
        parent::__construct();

        $this->creator = $creator;

        $this->packagePath = $packagePath;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $name = $this->input->getArgument('name');

        $table = $this->input->getOption('table');

        $create = $this->input->getOption('create');

        if ( ! $table && is_string($create)) $table = $create;

        //
        $this->writeMigration($name, $table, $create);

        $this->call('dump-autoload');
    }

    /**
     * Write the migration file to disk.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function writeMigration($name, $table, $create)
    {
        $path = $this->getMigrationPath();

        $file = pathinfo($this->creator->create($name, $path, $table, $create), PATHINFO_FILENAME);

        $this->line("<info>Created Migration:</info> $file");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('name', InputArgument::REQUIRED, 'The name of the Migration'),
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
            array('create', null, InputOption::VALUE_OPTIONAL, 'The table to be created.'),
            array('package', null, InputOption::VALUE_OPTIONAL, 'The package the Migration belongs to.', null),
            array('path', null, InputOption::VALUE_OPTIONAL, 'Where to store the Migration.', 'app'),
            array('table', null, InputOption::VALUE_OPTIONAL, 'The table to migrate.'),
        );
    }

}
