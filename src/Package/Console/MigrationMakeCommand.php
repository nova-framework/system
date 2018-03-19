<?php

namespace Nova\Package\Console;

use Nova\Package\Console\MakeCommand;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class MigrationMakeCommand extends MakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:package:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Package Migration file';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Migration';

    /**
     * Package folders to be created.
     *
     * @var array
     */
    protected $listFolders = array(
        'Database/Migrations/',
    );

    /**
     * Package files to be created.
     *
     * @var array
     */
    protected $listFiles = array(
        '{{filename}}.php',
    );

    /**
     * Package signature option.
     *
     * @var array
     */
    protected $signOption = array(
        'create',
        'table',
    );

    /**
     * Package stubs used to populate defined files.
     *
     * @var array
     */
    protected $listStubs = array(
        'default' => array(
            'migration.stub',
        ),
        'create' => array(
            'migration_create.stub',
        ),
        'table' => array(
            'migration_table.stub',
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
        $this->data['filename'] = $this->makeFileName($filePath);

        $this->data['className'] = basename($filePath);
        $this->data['tableName'] = 'dummy';
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
        $this->data['tableName'] = $option;
    }

    /**
     * Make FileName.
     *
     * @param string $filePath
     *
     * @return string
     */
    protected function makeFileName($filePath)
    {
        return date('Y_m_d_His') .'_' .strtolower(Str::snake(basename($filePath)));
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
            '{{className}}',
            '{{tablename}}',
        );

        $replaces = array(
            $this->data['filename'],
            $this->data['className'],
            $this->data['tableName'],
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
            array('name', InputArgument::REQUIRED, 'The name of the Migration.'),
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
            array('--create', null, InputOption::VALUE_OPTIONAL, 'The table to be created.'),
            array('--table',  null, InputOption::VALUE_OPTIONAL, 'The table to migrate.'),
        );
    }
}
