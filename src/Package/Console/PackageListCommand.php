<?php

namespace Nova\Package\Console;

use Nova\Console\Command;
use Nova\Package\PackageManager;
use Nova\Support\Str;


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
     * @var \Nova\Package\PackageManager
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
     * @param \Nova\Package\PackageManager $package
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
        $packages = $this->packages->all();

        if ($packages->isEmpty()) {
            return $this->error("Your application doesn't have any Packages.");
        }

        $this->displayPackages($this->getPackages());
    }

    /**
     * Get all Packages.
     *
     * @return array
     */
    protected function getPackages()
    {
        $packages = $this->packages->all();

        $results = array();

        foreach ($packages as $package) {
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
}
