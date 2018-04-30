<?php

namespace Nova\Packages\Console;

use Nova\Console\Command;
use Nova\Database\Migrations\Migrator;
use Nova\Packages\Console\MigrationTrait;
use Nova\Packages\PackageManager;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class PackageMigrateStatusCommand extends Command
{
    use MigrationTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'package:migrate:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of each migration';

    /**
     * @var \Nova\Packages\PackageManager
     */
    protected $packages;

    /**
     * The migrator instance.
     *
     * @var \Nova\Database\Migrations\Migrator
     */
    protected $migrator;


    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Nova\Database\Migrations\Migrator $migrator
     * @return \Nova\Database\Console\Migrations\StatusCommand
     */
    public function __construct(Migrator $migrator, PackageManager $packages)
    {
        parent::__construct();

        $this->migrator = $migrator;
        $this->packages  = $packages;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->migrator->repositoryExists()) {
            return $this->error('No migrations found.');
        }

        $slug = $this->argument('slug');

        if (! empty($slug)) {
            if (! $this->packages->exists($slug)) {
                return $this->error('package does not exist.');
            }

            return $this->status($slug);
        }

        foreach ($this->packages->all() as $package) {
            $this->comment('Migrations Status for package: ' .$package['name']);

            $this->status($package['slug']);
        }
    }

    protected function status($slug)
    {
        if (! $this->packages->exists($slug)) {
            return $this->error('package does not exist.');
        }

        $this->requireMigrations($slug);

        //
        $this->migrator->setConnection($this->input->getOption('database'));

        $ran = $this->migrator->getRepository()->getRan();

        //
        $migrations = array();

        foreach ($this->getAllMigrationFiles($slug) as $migration) {
            $migrations[] = in_array($migration, $ran) ? array('<info>Y</info>', $migration) : array('<fg=red>N</fg=red>', $migration);
        }

        if (count($migrations) > 0) {
            $this->table(array('Ran?', 'Migration'), $migrations);
        } else {
            $this->error('No migrations found');

            $this->output->writeln('');
        }
    }

    /**
     * Get all of the migration files.
     *
     * @param  string  $path
     * @return array
     */
    protected function getAllMigrationFiles($slug)
    {
        $path = $this->getMigrationPath($slug);

        return $this->migrator->getMigrationFiles($path);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::OPTIONAL, 'package slug.'),
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
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),
        );
    }
}
