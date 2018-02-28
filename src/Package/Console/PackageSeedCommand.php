<?php

namespace Nova\Package\Console;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Package\PackageManager;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class PackageSeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'package:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records for a specific or all Packages';

    /**
     * @var \Nova\Package\PackageManager
     */
    protected $packages;

    /**
     * Create a new command instance.
     *
     * @param \Nova\Package\PackageManager $packages
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

        if (! empty($slug)) {
            if (! $this->Packages->exists($slug)) {
                return $this->error('Package does not exist.');
            }

            if ($this->Packages->isEnabled($slug)) {
                $this->seed($slug);
            } else if ($this->option('force')) {
                $this->seed($slug);
            }

            return;
        }

        if ($this->option('force')) {
            $packages = $this->Packages->all();
        } else {
            $packages = $this->Packages->enabled();
        }

        foreach ($packages as $package) {
            $slug = $package['slug'];

            $this->seed($slug);
        }
    }

    /**
     * Seed the specific Package.
     *
     * @param string $package
     *
     * @return array
     */
    protected function seed($slug)
    {
        $package = $this->Packages->where('slug', $slug);

        $className = $package['namespace'] .'\Database\Seeds\DatabaseSeeder';

        if (! class_exists($className)) {
            return;
        }

        // Prepare the call parameters.
        $params = array();

        if ($this->option('class')) {
            $params['--class'] = $this->option('class');
        } else {
            $params['--class'] = $className;
        }

        if ($option = $this->option('database')) {
            $params['--database'] = $option;
        }

        if ($option = $this->option('force')) {
            $params['--force'] = $option;
        }

        $this->call('db:seed', $params);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::OPTIONAL, 'Package slug.')
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
            array('class', null, InputOption::VALUE_OPTIONAL, 'The class name of the Package\'s root seeder.'),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed.'),
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run while in production.'),
        );
    }
}
