<?php

namespace Nova\Support;

use Nova\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\PhpExecutableFinder;


class Composer
{
    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The working path to regenerate from.
     *
     * @var string
     */
    protected $workingPath;

    /**
     * Create a new Composer manager instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @param  string  $workingPath
     * @return void
     */
    public function __construct(Filesystem $files, $workingPath = null)
    {
        $this->files = $files;

        $this->workingPath = $workingPath;
    }

    /**
     * Regenerate the Composer autoloader files.
     *
     * @param  string  $extra
     * @return void
     */
    public function dumpAutoloads($extra = '')
    {
        $command = $this->findComposer() .' dump-autoload';

        if (! empty($extra)) {
            $command = trim($command .' ' .$extra);
        }

        $process = $this->getProcess();

        $process->setCommandLine($command);

        $process->run();
    }

    /**
     * Regenerate the optimized Composer autoloader files.
     *
     * @return void
     */
    public function dumpOptimized()
    {
        $this->dumpAutoloads('--optimize');
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        $composerPhar = $this->workingPath .DS .'composer.phar';

        if ($this->files->exists($composerPhar)) {
            $executable = with(new PhpExecutableFinder)->find(false);

            return ProcessUtils::escapeArgument($executable) .' ' .$composerPhar;
        }

        return 'composer';
    }

    /**
     * Get a new Symfony process instance.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function getProcess()
    {
        $process = new Process('', $this->workingPath);

        return $process->setTimeout(null);
    }

    /**
     * Set the working path used by the class.
     *
     * @param  string  $path
     * @return $this
     */
    public function setWorkingPath($path)
    {
        $this->workingPath = realpath($path);

        return $this;
    }

}
