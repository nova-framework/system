<?php

namespace Nova\Foundation\Console;

use Nova\Console\GeneratorCommand;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;


class PolicyMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:policy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Policy class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Policy';


    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->replaceUserNamespace(
            parent::buildClass($name)
        );

        $model = $this->option('model');

        return $model ? $this->replaceModel($stub, $model) : $stub;
    }

    /**
     * Replace the User model namespace.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceUserNamespace($stub)
    {
        $config = $this->container['config'];

        //
        $namespaceModel = $config->get('auth.providers.users.model', 'App\Models\User');

        $model = class_basename(trim($namespaceModel, '\\'));

        //
        $stub = str_replace('{{fullUserModel}}', $namespaceModel, $stub);

        return str_replace('{{userModel}}', $model, $stub);
    }

    /**
     * Replace the model for the given stub.
     *
     * @param  string  $stub
     * @param  string  $model
     * @return string
     */
    protected function replaceModel($stub, $model)
    {
        $model = str_replace('/', '\\', $model);

        $namespaceModel = $this->container->getNamespace() .'Models\\' .$model;

        if (Str::startsWith($model, '\\')) {
            $stub = str_replace('{{fullModel}}', trim($model, '\\'), $stub);
        } else {
            $stub = str_replace('{{fullModel}}', $namespaceModel, $stub);
        }

        $stub = str_replace(
            "use {$namespaceModel};\nuse {$namespaceModel};", "use {$namespaceModel};", $stub
        );

        $model = class_basename(trim($model, '\\'));

        $stub = str_replace('{{model}}', $model, $stub);

        $stub = str_replace('{{camelModel}}', Str::camel($model), $stub);

        return str_replace('{{pluralModel}}', Str::plural(Str::camel($model)), $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->option('model')) {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/policy.stub');
        } else {
            return realpath(__DIR__) .str_replace('/', DS, '/stubs/policy.plain.stub');
        }
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace .'\Policies';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to.'),
        );
    }
}
