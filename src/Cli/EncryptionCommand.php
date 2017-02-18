<?php
namespace Nova\Cli;

use Nova\Support\Facades\Config;
use Nova\Support\Facades\File;
use Nova\Support\Str;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class EncryptionCommand extends Command
{
    private $length;

    protected function configure()
    {
        $this
            ->setName('make:key')
            ->setDescription('Generate an encryption key for the config file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentKey = Config::get('app.key');

        list($path, $contents) = $this->getKeyFile();

        $key = $this->getRandomKey();

        $contents = str_replace($currentKey, $key, $contents);

        File::put($path, $contents);

        $output->writeln("<info>An Encryption key has been generated.</>");
    }

    /**
     * Get the key file and contents.
     *
     * @return array
     */
    protected function getKeyFile()
    {
        $path = app_path('Config/App.php');

        $contents = File::get($path);

        return array($path, $contents);
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function getRandomKey()
    {
        return Str::random(32);
    }
}
