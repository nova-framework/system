<?php

namespace Nova\Package\Console;

use Nova\Package\Console\MakeCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ListenerMakeCommand extends MakeCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:package:listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Package Event Listener class';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Listener';

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
        'Listeners/',
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
            'listener.stub',
        ),
        'queued' => array(
            'listener-queued.stub',
        ),
    );


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->option('event')) {
            return $this->error('Missing required option: --event');
        }

        parent::handle();
    }

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

        //
        $this->data['fullEvent'] = $option = $this->option('event');

        $this->data['event'] = class_basename($option);
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

            '{{event}}',
            '{{fullEvent}}',
        );

        $replaces = array(
            $this->data['filename'],
            $this->data['path'],
            $this->data['namespace'],
            $this->data['className'],

            $this->data['event'],
            $this->data['fullEvent'],
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
            array('event', 'e', InputOption::VALUE_REQUIRED, 'The Event class being listened for.'),

            array('queued', null, InputOption::VALUE_NONE, 'Indicates that Event Listener should be queued.'),
        );
    }
}
