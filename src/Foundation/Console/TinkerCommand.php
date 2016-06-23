<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;

class TinkerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tinker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Interact with your application";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->supportsBoris()) {
            $this->runBorisShell();
        } else {
            $this->comment('Full REPL not supported. Falling back to simple shell.');

            $this->runPlainShell();
        }
    }

    /**
     * Run the Boris REPL with the current context.
     *
     * @return void
     */
    protected function runBorisShell()
    {
        $this->setupBorisErrorHandling();

        with(new \Boris\Boris('> '))->start();
    }

    /**
     * Setup the Boris exception handling.
     *
     * @return void
     */
    protected function setupBorisErrorHandling()
    {
        restore_error_handler(); restore_exception_handler();

        $this->laravel->make('artisan')->setCatchExceptions(false);

        $this->laravel->error(function() { return ''; });
    }

    /**
     * Run the plain Artisan tinker shell.
     *
     * @return void
     */
    protected function runPlainShell()
    {
        $input = $this->prompt();

        while ($input != 'quit') {
            try {
                if (starts_with($input, 'dump ')) {
                    $input = 'var_dump('.substr($input, 5).');';
                }

                eval($input);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }

            $input = $this->prompt();
        }
    }

    /**
     * Prompt the developer for a command.
     *
     * @return string
     */
    protected function prompt()
    {
        $question = $this->getHelperSet()->get('question');

        return $question->ask($this->input, $this->output, "<info>></info>");
    }

    /**
     * Determine if the current environment supports Boris.
     *
     * @return bool
     */
    protected function supportsBoris()
    {
        return extension_loaded('readline') && extension_loaded('posix') && extension_loaded('pcntl');
    }

}
