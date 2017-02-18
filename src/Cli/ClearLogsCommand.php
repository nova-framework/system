<?php
namespace Nova\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ClearLogsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('clear:logs')
            ->setDescription('Clears the log files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logsPath = STORAGE_PATH .'logs';

        if (! is_dir($logsPath)) {
            $output->writeln("<error>Logs directory does not exist.</>");

            return true;
        }

        $logs = glob($logsPath .DS .'*.log');

        $this->clearLogs($logs);

        $output->writeln("<info>The log files have been cleared.</>");
    }

    public function clearLogs($files)
    {
        foreach ($files as $file) {
            if (is_file($file) && (basename($file) != ".gitignore")) {
                unlink($file);
            }
        }
    }
}
