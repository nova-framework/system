<?php

namespace Nova\Module\Console;

use Nova\Module\Console\MakeCommand;

use Symfony\Component\Console\Input\InputArgument;


class MakeEventCommand extends MakeCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:module:event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Module Event class';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Event';

    /**
     * Module folders to be created.
     *
     * @var array
     */
    protected $listFolders = array(
        'Events/',
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
            'event.stub',
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

        //
        $this->data['rootNamespace'] = $this->container->getNamespace();
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

            '{{rootNamespace}}',

        );

        $replaces = array(
            $this->data['filename'],
            $this->data['path'],
            $this->data['namespace'],
            $this->data['className'],

            $this->data['rootNamespace'],
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
}
