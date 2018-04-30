<?php

namespace Nova\Packages\Console;

use Nova\Console\Command;
use Nova\Packages\PackageManager;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;


class PackageListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'package:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all Framework Packages';

    /**
     * @var \Nova\Packages\PackageManager
     */
    protected $packages;

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Package', 'Slug', 'Order', 'Location', 'Type', 'Status'];

    /**
     * Create a new command instance.
     *
     * @param \Nova\Packages\PackageManager $package
     */
    public function __construct(PackageManager $packages)
    {
        parent::__construct();

        $this->packages = $packages;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->option('type');

        if (! is_null($type) && ! in_array($type, array('package', 'module', 'theme'))) {
            return $this->error("Invalid Packages type [$type].");
        }

        $packages = $this->getPackages($type);

        if (empty($packages)) {
            if (! is_null($type)) {
                return $this->error("Your application doesn't have any Packages of type [$type].");
            }

            return $this->error("Your application doesn't have any Packages.");
        }

        $this->displayPackages($packages);
    }

    /**
     * Get all Packages.
     *
     * @return array
     */
    protected function getPackages($type)
    {
        $packages = $this->packages->all();

        if (! is_null($type)) {
            $packages = $packages->where('type', $type);
        }

        $results = array();

        foreach ($packages->sortBy('basename') as $package) {
            $results[] = $this->getPackageInformation($package);
        }

        return array_filter($results);
    }

    /**
     * Returns Package manifest information.
     *
     * @param string $package
     *
     * @return array
     */
    protected function getPackageInformation($package)
    {
        $location = ($package['location'] === 'local') ? 'Local' : 'Vendor';

        $type = Str::title($package['type']);

        if ($this->packages->isEnabled($package['slug'])) {
            $status = 'Enabled';
        } else {
            $status = 'Disabled';
        }

        return array(
            'name'     => $package['name'],
            'slug'     => $package['slug'],
            'order'    => $package['order'],
            'location' => $location,
            'type'     => $type,
            'status'   => $status,
        );
    }

    /**
     * Display the Package information on the console.
     *
     * @param array $packages
     */
    protected function displayPackages(array $packages)
    {
        $this->table($this->headers, $packages);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('--type', null, InputOption::VALUE_REQUIRED, 'The type of Packages', null),
        );
    }
}
