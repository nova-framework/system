<?php

namespace Nova\Module\Console;

use Nova\Module\Console\MakeCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class ControllerMakeCommand extends MakeCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:module:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Module Controller class';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Controller';

    /**
     * Module folders to be created.
     *
     * @var array
     */
    protected $listFolders = array(
        'Controllers/',
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
     * Module signature option.
     *
     * @var array
     */
    protected $signOption = array(
        'resource',
    );

    /**
     * Module stubs used to populate defined files.
     *
     * @var array
     */
    protected $listStubs = array(
        'default' => array(
            'controller.stub',
        ),
        'resource' => array(
            'controller_resource.stub',
        ),
    );

    /**
     * Resolve Container after getting file path.
     *
     * @param string $FilePath
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
            array('slug', InputArgument::REQUIRED, 'The slug of the Module.'),
            array('name', InputArgument::REQUIRED, 'The name of the Controller class.'),
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
            array('--resource', null, InputOption::VALUE_NONE, 'Generate a module resource controller class'),
        );
    }
}
