<?php

namespace Nova\Packages\Console;

use Nova\Packages\Console\MakeCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class JobMakeCommand extends MakeCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:package:job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Package Job class';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Job';

    /**
     * package signature option.
     *
     * @var array
     */
    protected $signOption = array(
        'queued',
    );

    /**
     * package folders to be created.
     *
     * @var array
     */
    protected $listFolders = array(
        'Jobs/',
    );

    /**
     * package files to be created.
     *
     * @var array
     */
    protected $listFiles = array(
        '{{filename}}.php',
    );

    /**
     * package stubs used to populate defined files.
     *
     * @var array
     */
    protected $listStubs = array(
        'default' => array(
            'job.stub',
        ),
        'queued' => array(
            'job-queued.stub',
        ),
    );


    /**
     * Resolve Container after getting file path.
     *
     * @param string $filePath
     *
     * @return array
     */
    protected function resolveByPath($filePath)
    {
        $this->data['filename']  = $this->makeFileName($filePath);
        $this->data['namespace'] = $this->getNamespace($filePath);

        $this->data['path'] = $this->getBaseNamespace();

        $this->data['className'] = basename($filePath);
    }

    /**
     * Replace placeholder text with correct values.
     *
     * @return string
     */
    protected function formatContent($content)
    {
        $searches = array(
            '{{filename}}',
            '{{path}}',
            '{{namespace}}',
            '{{className}}',
        );

        $replaces = array(
            $this->data['filename'],
            $this->data['path'],
            $this->data['namespace'],
            $this->data['className'],
        );

        return str_replace($searches, $replaces, $content);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('slug', InputArgument::REQUIRED, 'The slug of the Package.'),
            array('name', InputArgument::REQUIRED, 'The name of the Event class.'),
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
            array('queued', null, InputOption::VALUE_NONE, 'Indicates that Job should be queued.'),
        );
    }
}
