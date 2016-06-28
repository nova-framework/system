<?php

namespace Nova\Module\Console;

use Nova\Console\Command;
use Nova\Foundation\Application;
use Nova\Foundation\Composer;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Migrate Command
 */
class ModuleMigrateCommand extends AbstractCommand
{
    /**
     * Name of the Command
     *
     * @var string
     */
    protected $name = 'modules:migrate';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Run Migrations for Modules.';

    /**
     * List of Migrations
     *
     * @var array
     */
    protected $migrationList = array();

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Migrating Modules');

        $files = $this->framework['files'];

        $basePath = $this->framework['path.base'];

        // Get all Modules or a specific one.
        if ($moduleName = $this->input->getArgument('module')) {
            $modules = array($this->framework['modules']->module($moduleName));
        } else {
            $modules = $this->framework['modules']->modules();
        }

        foreach ($modules as $module) {
            if (! is_null($module)) {
                if ($files->exists($module->path('Migrations'))) {
                    // Prepare params
                    $path  = ltrim(str_replace($basePath, '', $module->path()), DS) .DS ."Migrations";

                    $info = array('path' => $path);

                    // Add to migration list
                    array_push($this->migrationList, $info);

                    $this->info("Added '" . $module->name() . "' to migration list.");
                } else {
                    $this->line("Module <info>'" . $module->name() . "'</info> has no migrations.");
                }
            } else {
                $this->error("Module '" . $moduleName . "' does not exist.");
            }
        }

        if (count($this->migrationList)) {
            $this->info("Running Migrations...");

            // Process migration list
            $this->runPathsMigration();
        }

        if ($this->input->getOption('seed')) {
            $this->info("Running Seeding Command...");

            $this->call('modules:seed');
        }
    }

    /**
     * Run paths migrations
     *
     * @return void
     */
    protected function runPathsMigration()
    {
        $fileService = new Filesystem();

        $tmpPath = app_path('storage') .DS . 'Migrations';

        if (! is_dir($tmpPath) && ! $fileService->exists($tmpPath)) {
            $fileService->mkdir($tmpPath);
        }

        $this->info("Gathering migration files to {$tmpPath}");

        // Copy all files to storage/migrations
        foreach ($this->migrationList as $migration) {
            $fileService->mirror($migration['path'], $tmpPath);
        }

        //call migrate command on temporary path
        $this->info("Migrating...");

        $opts = array('--path' => ltrim(str_replace(base_path(), '', $tmpPath), DS));

        if($this->input->getOption('force')) {
            $opts['--force'] = true;
        }

        if ($this->input->getOption('database')) {
            $opts['--database'] = $this->input->getOption('database');
        }

        $this->call('migrate', $opts);

        // Delete all temp migration files
        $this->info("Cleaning temporary files");

        $fileService->remove($tmpPath);

        // Done
        $this->info("DONE!");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('module', InputArgument::OPTIONAL, 'The name of module being migrated.'),
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
            array('seed',     null, InputOption::VALUE_NONE,     'Indicates if the Module should seed the database.'),
            array('force',    '-f', InputOption::VALUE_NONE,     'Force the operation to run when in production.'),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The Database Connection.', null)
        );
    }

}
