<?php

namespace Nova\Package\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Filesystem\Filesystem;
use Nova\Database\Migrations\Migrator;
use Nova\Package\Console\MigrationTrait;
use Nova\Package\PackageManager;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class PackageMigrateResetCommand extends Command
{
    use ConfirmableTrait;
    use MigrationTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'package:migrate:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations for a specific or all Packages';

    /**
     * @var \Nova\Package\PackageManager
     */
    protected $packages;

    /**
     * @var Migrator
     */
    protected $migrator;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param PackageManager  $packages
     * @param Filesystem     $files
     * @param Migrator       $migrator
     */
    public function __construct(PackageManager $packages, Filesystem $files, Migrator $migrator)
    {
        parent::__construct();

        $this->packages  = $packages;
        $this->files    = $files;
        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $slug = $this->argument('slug');

        if (! empty($slug)) {
            if (! $this->packages->exists($slug)) {
                return $this->error('Package does not exist.');
            }

            if ($this->packages->isEnabled($slug)) {
                return $this->reset($slug);
            }

            return;
        }

        $packages = $this->packages->enabled()->reverse();

        foreach ($packages as $package) {
            $this->comment('Resetting the migrations of Package: ' .$package['name']);

            $this->reset($package['slug']);
        }
    }

    /**
     * Run the migration reset for the specified Package.
     *
     * Migrations should be reset in the reverse order that they were
     * migrated up as. This ensures the database is properly reversed
     * without conflict.
     *
     * @param string $slug
     *
     * @return mixed
     */
    protected function reset($slug)
    {
        if (! $this->packages->exists($slug)) {
            return $this->error('Package does not exist.');
        }

        $this->requireMigrations($slug);

        //
        $this->migrator->setconnection($this->input->getOption('database'));

        $pretend = $this->input->getOption('pretend');

        while (true) {
            $count = $this->migrator->rollback($pretend, $slug);

            foreach ($this->migrator->getNotes() as $note) {
                $this->output->writeln($note);
            }

            if ($count == 0) break;
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
            array('slug', InputArgument::OPTIONAL, 'Package slug.'),
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
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
            array('pretend', null, InputOption::VALUE_OPTIONAL, 'Dump the SQL queries that would be run.'),
        );
    }
}
