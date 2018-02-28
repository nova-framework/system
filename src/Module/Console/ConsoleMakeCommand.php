<?php

namespace Nova\Module\Console;

use Nova\Module\Console\MakeCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ConsoleMakeCommand extends MakeCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:module:console';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Module Forge command';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Command';

    /**
     * Module folders to be created.
     *
     * @var array
     */
    protected $listFolders = array(
        'Console/Commands/',
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
            'console.stub',
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
        $this->data['path']      = $this->getBaseNamespace();
        $this->data['className'] = basename($filePath);

        //
        $this->data['command'] = $this->option('command');
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
            '{{command}}',
        );

        $replaces = array(
            $this->data['filename'],
            $this->data['path'],
            $this->data['namespace'],
            $this->data['className'],
            $this->data['command'],
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
            array('name', InputArgument::REQUIRED, 'The name of the Model class.'),
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
            array('command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned.', 'command:name'),
        );
    }
}
