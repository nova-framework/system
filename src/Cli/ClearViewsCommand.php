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
        $path = Config::get('view.compiled', 'storage/views');

        if (!is_dir($path)) {
            $output->writeln("<error>Views directory does not exist. path: $path</>");
            return true;
        }

        self::cleanCache($path);
        $output->writeln("<info>Views directory has been cleaned. path: $path</>");
    }

   protected function cleanCache($dir)
   {
       if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != ".." && $object != ".gitignore") {
                    if (is_dir($dir."/".$object)) {
                        self::cleanCache($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            @rmdir($dir);
        }
    }
}
