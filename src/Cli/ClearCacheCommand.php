<?php
namespace Nova\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Nova\Support\Facades\Config;


class ClearCacheCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('clear:cache')
            ->setDescription('Clears the cache folder')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = Config::get('cache.path', STORAGE_PATH .'cache');

        if (! is_dir($path)) {
            $output->writeln("<error>Cache directory does not exist. path: $path</>");

            return true;
        }

        self::cleanCache($path);

        $output->writeln("<info>Cache directory has been cleaned. path: $path</>");
    }

   protected function cleanCache($dir)
   {
       if (is_dir($dir)) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if (($object != ".") && ($object != "..") && ($object != ".gitignore")) {
                    if (is_dir($dir .DS .$object)) {
                        self::cleanCache($dir .DS .$object);
                    } else {
                        unlink($dir .DS .$object);
                    }
                }
            }

            @rmdir($dir);
        }
    }
}
