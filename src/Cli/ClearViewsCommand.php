<?php
namespace Nova\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Nova\Support\Facades\Config;


class ClearViewsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('clear:views')
            ->setDescription('Clears the view cache folder')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = Config::get('view.compiled', STORAGE_PATH .'views');

        if (! is_dir($path)) {
            $output->writeln("<error>Views directory does not exist. path: $path</>");

            return true;
        }

        // Get the files from Views Cache directory.
        $paths = glob($path .DS .'*.php');

        self::cleanCache($paths);

        $output->writeln("<info>Views directory has been cleaned. path: $path</>");
    }

    protected function cleanCache($files)
    {
        foreach ($files as $file) {
            if (is_file($file) && (basename($file) != ".gitignore")) {
                unlink($file);
            }
        }
    }
}
