<?php

namespace Nova\Database;

use Nova\Console\Command;
use Nova\Container\Container;


class Seeder
{
    /**
     * The container instance.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * The console command instance.
     *
     * @var \Nova\Console\Command
     */
    protected $command;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {}

    /**
     * Seed the given connection from the given path.
     *
     * @param  string  $class
     * @return void
     */
    public function call($class)
    {
        $this->resolve($class)->run();

        if (isset($this->command)) {
            $this->command->getOutput()->writeln("<info>Seeded:</info> $class");
        }
    }

    /**
     * Resolve an instance of the given seeder class.
     *
     * @param  string  $class
     * @return \Nova\Database\Seeder
     */
    protected function resolve($class)
    {
        if (isset($this->container)) {
            $instance = $this->container->make($class);

            $instance->setContainer($this->container);
        } else {
            $instance = new $class;
        }

        if (isset($this->command)) {
            $instance->setCommand($this->command);
        }

        return $instance;
    }

    /**
     * Set the IoC Container instance.
     *
     * @param  \Nova\Container\Container  $container
     * @return \Nova\Database\Seeder
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set the console command instance.
     *
     * @param  \Nova\Console\Command  $command
     * @return \Nova\Database\Seeder
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;

        return $this;
    }

}
