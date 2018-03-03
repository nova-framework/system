<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Foundation\Publishers\ViewPublisher;
use Nova\Package\PackageManager;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


class ViewPublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'view:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Publish a Package views to the application";

    /**
     * The plugins manager instance.
     *
     * @var \Nova\Package\PackageManager
     */
    protected $plugins;

    /**
     * The view publisher instance.
     *
     * @var \Nova\Foundation\ViewPublisher
     */
    protected $publisher;


    /**
     * Create a new view publish command instance.
     *
     * @param  \Nova\Foundation\ViewPublisher  $view
     * @return void
     */
    public function __construct(PackageManager $plugins, ViewPublisher $publisher)
    {
        parent::__construct();

        $this->plugins = $plugins;

        $this->publisher = $publisher;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $package = $this->input->getArgument('package');

        // Direct specification of the package and Views path.
        if (! is_null($path = $this->getPath())) {
            $this->publisher->publish($package, $path);
        }

        // For the packages which are registered as plugins.
        else if ($this->plugins->exists($package)) {
            if (Str::length($package) > 3) {
                $slug = Str::snake($package);
            } else {
                $slug = Str::lower($package);
            }

            $properties = $this->plugins->where('slug', $slug);

            //
            $package = $properties['name'];

            if ($properties['type'] == 'package') {
                $path = $properties['path'] .str_replace('/', DS, '/src/Views');
            } else {
                $path = $properties['path'] .DS . 'Views';
            }

            $this->publisher->publish($package, $path);
        }

        // For other packages located in vendor.
        else {
            $this->publisher->publishPackage($package);
        }

        $this->output->writeln('<info>Views published for package:</info> '.$package);
    }

    /**
     * Get the specified path to the files.
     *
     * @return string
     */
    protected function getPath()
    {
        $path = $this->input->getOption('path');

        if (! is_null($path)) {
            return $this->container['path.base'] .DS .$path;
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
            array('package', InputArgument::REQUIRED, 'The name of the package being published.'),
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
            array('path', null, InputOption::VALUE_OPTIONAL, 'The path to the source view files.', null),
        );
    }

}
