<?php

namespace Nova\Database\Console\Seeds;

use Nova\Console\Command;
use Nova\Console\ConfirmableTrait;
use Nova\Database\ConnectionResolverInterface as Resolver;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;


class SeedCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records';

    /**
     * The connection resolver instance.
     *
     * @var \Nova\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
     *
     * @param  \Nova\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->resolver->setDefaultConnection($this->getDatabase());

        $this->getSeeder()->run();
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Nova\Database\Seeder
     */
    protected function getSeeder()
    {
        $className = str_replace('/', '\\', $this->input->getOption('class'));

        $rootNamespace = $this->container->getNamespace();

        if (! Str::startsWith($className, $rootNamespace) && ! Str::contains($className, '\\')) {
            $className = $rootNamespace .'Database\Seeds\\' .$className;
	}

        $instance = $this->container->make($className);

        return $instance->setContainer($this->container)->setCommand($this);
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->container['config']['database.default'];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('class',    null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'DatabaseSeeder'),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'),
            array('force',    null, InputOption::VALUE_NONE,     'Force the operation to run when in production.'),
        );
    }
}
