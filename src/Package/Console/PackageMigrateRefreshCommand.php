<?php

namespace Nova\Package\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Package\PackageManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class PackageMigrateRefreshCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'package:migrate:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations for a specific or all Packages';

    /**
     * @var \Nova\Package\PackageManager
     */
    protected $packages;

    /**
     * Create a new command instance.
     *
     * @param PackageManager  $package
     */
    public function __construct(PackageManager $packages)
    {
        parent::__construct();

        $this->Packages = $packages;
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

        if (! $this->Packages->exists($slug)) {
            return $this->error('Package does not exist.');
        }

        $this->call('package:migrate:reset', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
            '--force'    => $this->option('force'),
            '--pretend'  => $this->option('pretend'),
        ));

        $this->call('package:migrate', array(
            'slug'       => $slug,
            '--database' => $this->option('database'),
        ));

        if ($this->needsSeeding()) {
            $this->runSeeder($slug, $this->option('database'));
        }

        $this->info('Package has been refreshed.');
    }

    /**
     * Determine if the developer has requested database seeding.
     *
     * @return bool
     */
    protected function needsSeeding()
    {
        return $this->option('seed');
    }

    /**
     * Run the Package seeder command.
     *
     * @param string $database
     */
    protected function runSeeder($slug = null, $database = null)
    {
        $this->call('package:seed', array(
            'slug'       => $slug,
            '--database' => $database,
        ));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::REQUIRED, 'Package slug.'),
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
            array('pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
        );
    }
}
