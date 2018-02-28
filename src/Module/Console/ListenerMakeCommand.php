<?php

namespace Nova\Module\Console;

use Nova\Module\Console\MakeCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ListenerMakeCommand extends MakeCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:module:listener';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Module Event Listener class';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Listener';

    /**
     * Module signature option.
     *
     * @var array
     */
    protected $signOption = array(
        'event',
    );

    /**
     * Module folders to be created.
     *
     * @var array
     */
    protected $listFolders = array(
        'Listeners/',
    );

    /**
     * Module files to be created.
     *
     * @var array
     */
    protected $listFiles = array(
        '{{filename}}.php',
    );

    /**
     * Module stubs used to populate defined files.
     *
     * @var array
     */
    protected $listStubs = array(
        'default' => array(
            'listener.stub',
        ),
        'event' => array(
            'listener.stub',
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
        $this->data['event']     = 'dummy';
        $this->data['fullEvent'] = 'dummy';
    }

    /**
     * Resolve Container after getting input option.
     *
     * @param string $option
     *
     * @return array
     */
    protected function resolveByOption($option)
    {
        $this->data['fullEvent'] = $option;

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
            array('slug', InputArgument::REQUIRED, 'The slug of the Module.'),
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
            array('event', 'e', InputOption::VALUE_REQUIRED, 'The event class being listened for.'),
        );
    }
}
