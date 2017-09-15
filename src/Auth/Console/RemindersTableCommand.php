<?php

namespace Nova\Auth\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Support\Facades\Config;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputArgument;


class RemindersTableCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'auth:reminders-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the password reminders table';

    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new reminder table command instance.
     *
     * @param  \Nova\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $broker = $this->argument('name');

        if (empty($name)) {
            $broker = Config::get('auth.defaults.reminder', 'user');
        }

        $name = 'create_' .$broker .'_password_reminders_table';

        $fullPath = $this->createBaseMigration($name);

        $this->files->put($fullPath, $this->getMigrationStub($name, $broker));

        $this->info('Migration created successfully!');

        $this->call('optimize');
    }

    /**
     * Create a base migration file for the reminders.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function createBaseMigration($name)
    {
        $path = $this->nova['path'] .DS .'Database' .DS .'Migrations';

        return $this->nova['migration.creator']->create($name, $path);
    }

    /**
     * Get the contents of the reminder migration stub.
     *
     * @param  string  $name
     * @param  string  $broker
     *
     * @return string
     */
    protected function getMigrationStub($name, $broker)
    {
        $path = realpath(__DIR__) .str_replace('/', DS, '/stubs/reminders.stub');

        $stub = $this->files->get($path);

        return str_replace(
            array(
                'CreatePasswordRemindersTable',
                'password_reminders'
            ),
            array(
                Str::studly($name),
                $this->getTable($broker)
            ),
            $stub
        );
    }

    /**
     * Get the password reminder table name.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function getTable($name)
    {
        return $this->nova['config']->get("auth.reminders.{$name}.table");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('name', InputArgument::OPTIONAL, 'The name of the password broker.'),
        );
    }
}
