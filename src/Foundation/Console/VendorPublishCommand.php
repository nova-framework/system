<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Support\ServiceProvider;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use FilesystemIterator;


class VendorPublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'vendor:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Publish any publishable assets from vendor packages";

    /**
     * The asset publisher instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;


    /**
     * Create a new vendor publish command instance.
     *
     * @param  \Nova\Foundation\VendorPublisher  $publisher
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
    public function handle()
    {
        $group = $this->input->getArgument('group');

        if (is_null($group)) {
            return $this->publish();
        }

        $groups = explode(',', $group);

        foreach($groups as $group) {
            $this->publish($group);
        }
    }

    /**
     * Publish the assets for a given group name.
     *
     * @param  string|null  $group
     * @return void
     */
    protected function publish($group = null)
    {
        $paths = ServiceProvider::pathsToPublish($group);

        if (empty($paths)) {
            if (is_null($group)) {
                return $this->comment("Nothing to publish.");
            }

            return $this->comment("Nothing to publish for group [{$group}].");
        }

        foreach ($paths as $from => $to) {
            if ($this->files->isFile($from)) {
                $this->publishFile($from, $to);
            } else if ($this->files->isDirectory($from)) {
                $this->publishDirectory($from, $to);
            } else {
                $this->error("Can't locate path: <{$from}>");
            }
        }

        if (is_null($group)) {
            return $this->info("Publishing complete!");
        }

        $this->info("Publishing complete for group [{$group}]!");
    }

    /**
     * Publish the file to the given path.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if ($this->files->exists($to) && ! $this->option('force')) {
            return;
        }

        $directory = dirname($to);

        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->copy($from, $to);

        $this->status($from, $to, 'File');
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        $this->copyDirectory($from, $to);

        $this->status($from, $to, 'Directory');
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  bool  $force
     * @return bool
     */
    public function copyDirectory($directory, $destination)
    {
        if (! $this->files->isDirectory($directory)) {
            return false;
        }

        if (! $this->files->isDirectory($destination)) {
            $this->files->makeDirectory($destination, 0777, true);
        }

        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            $target = $destination .DS .$item->getBasename();

            if ($item->isDir()) {
                if (! $this->copyDirectory($item->getPathname(), $target)) {
                    return false;
                }

                continue;
            }

            // The current item is a file.
            if ($this->files->exists($target) && ! $this->option('force')) {
                continue;
            } else if (! $this->files->copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Write a status message to the console.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(base_path(), '', realpath($from));

        $to = str_replace(base_path(), '', realpath($to));

        $this->output->writeln('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('group', InputArgument::OPTIONAL, 'The name of assets group being published.'),
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
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
        );
    }
}
