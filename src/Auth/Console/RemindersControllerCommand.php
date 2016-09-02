<?php

namespace Nova\Auth\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;

use Symfony\Component\Console\Input\InputOption;


class RemindersControllerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'auth:reminders-controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a stub password reminder controller';

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
        $destination = $this->getPath() .DS .'Reminders.php';

        if (! $this->files->exists($destination)) {
            $this->files->copy(__DIR__ .DS .'stubs' .DS .'controller.stub', $destination);

            $this->info('Password reminders controller created successfully!');

            $this->comment("Route: Route::controller('password', 'RemindersController');");
        } else {
            $this->error('Password reminders controller already exists!');
        }
    }

    /**
     * Get the path to the migration directory.
     *
     * @return string
     */
    private function getPath()
    {
        if (! $path = $this->input->getOption('path')) {
            $path = $this->nova['path'] .DS .'Controllers';
        }

        return rtrim($path, '/');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('path', null, InputOption::VALUE_OPTIONAL, 'The directory where the controller should be placed.', null),
        );
    }

}
