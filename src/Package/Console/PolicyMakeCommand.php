<?php

namespace Nova\Package\Console;

use Nova\Package\Console\MakeCommand;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


class PolicyMakeCommand extends MakeCommand
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:package:policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Package Policy class';

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type = 'Policy';

    /**
     * Package folders to be created.
     *
     * @var array
     */
    protected $listFolders = array(
        'Policies/',
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
        'model',
    );

    /**
     * Package stubs used to populate defined files.
     *
     * @var array
     */
    protected $listStubs = array(
        'default' => array(
            'policy.plain.stub',
        ),
        'model' => array(
            'policy.stub',
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

        $this->data['className'] = basename($filePath);

        //
        $this->data['model'] = 'dummy';

        $this->data['fullModel']   = 'dummy';
        $this->data['camelModel']  = 'dummy';
        $this->data['pluralModel'] = 'dummy';

        $this->data['userModel']     = 'dummy';
        $this->data['fullUserModel'] = 'dummy';
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
        $model = str_replace('/', '\\', $option);

        $namespaceModel = $this->container->getNamespace() .'Models\\' .$model;

        if (Str::startsWith($model, '\\')) {
            $this->data['fullModel'] = trim($model, '\\');
        } else {
            $this->data['fullModel'] = $namespaceModel;
        }

        $this->data['model'] = $model = class_basename(trim($model, '\\'));

        $this->data['camelModel'] = Str::camel($model);

        $this->data['pluralModel'] = Str::plural(Str::camel($model));

        //
        $config = $this->container['config'];

        $this->data['fullUserModel'] = $model = $config->get('auth.providers.users.model', 'App\Models\User');

        $this->data['userModel'] = class_basename(trim($model, '\\'));
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
            '{{namespace}}',
            '{{className}}',
            '{{model}}',
            '{{fullModel}}',
            '{{camelModel}}',
            '{{pluralModel}}',
            '{{userModel}}',
            '{{fullUserModel}}',

        );

        $replaces = array(
            $this->data['filename'],
            $this->data['namespace'],
            $this->data['className'],
            $this->data['model'],
            $this->data['fullModel'],
            $this->data['camelModel'],
            $this->data['pluralModel'],
            $this->data['userModel'],
            $this->data['fullUserModel'],
        );

        $content = str_replace($searches, $replaces, $content);

        //
        $class = $this->data['fullModel'];

        return str_replace("use {$class};\nuse {$class};", "use {$class};", $content);
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
            array('name', InputArgument::REQUIRED, 'The name of the Policy class.'),
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
            array('--model', null, InputOption::VALUE_OPTIONAL, 'The model that the policy applies to.'),
        );
    }
}
