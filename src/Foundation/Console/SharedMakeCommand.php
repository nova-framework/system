<?php

namespace Nova\Foundation\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Support\Arr;

use Symfony\Component\Console\Helper\ProgressBar;


class SharedMakeCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:shared';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the standard Shared namespace';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The name of the custom helpers file.
     *
     * @var string
     */
    protected $helpers = 'shared/Support/helpers.php';


    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        //
        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = base_path('shared');

        if ($this->files->exists($path)) {
            $this->error('The Shared namespace already exists!');

            return false;
        }

        $steps = array(
            'Generating folders...'          => 'generateFolders',
            'Generating files...'            => 'generateFiles',
            'Updating the composer.json ...' => 'updateComposerJson',
        );

        $progress = new ProgressBar($this->output, count($steps));

        $progress->start();

        foreach ($steps as $message => $method) {
            $progress->setMessage($message);

            call_user_func(array($this, $method), $path);

            $progress->advance();
        }

        $progress->finish();

        $this->info("\nGenerating optimized class loader");

        $this->container['composer']->dumpOptimized();

        $this->info("Package generated successfully.");
    }

    /**
     * Generate the folders.
     *
     * @param string $type
     * @return void
     */
    protected function generateFolders($path)
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true, true);
        }

        $this->files->makeDirectory($path .DS .'Language');
        $this->files->makeDirectory($path .DS .'Support');

        // Generate the Language folders.
        $languages = $this->container['config']->get('languages', array());

        foreach (array_keys($languages) as $code) {
            $directory = $path .DS .'Language' .DS .strtoupper($code);

            $this->files->makeDirectory($directory);
        }
    }

    /**
     * Generate the files.
     *
     * @param string $type
     * @return void
     */
    protected function generateFiles($path)
    {
        //
        // Generate the custom helpers file.

        $content ='<?php

//----------------------------------------------------------------------
// Custom Helpers
//----------------------------------------------------------------------
';

        $filePath = $path .DS .'Support' .DS .'helpers.php';

        $this->files->put($filePath, $content);

        //
        // Generate the Language files.

        $content ='<?php

return array (
);';

        $languages = $this->container['config']->get('languages', array());

        foreach (array_keys($languages) as $code) {
            $filePath = $path .DS .'Language' .DS .strtoupper($code) .DS .'messages.php';

            $this->files->put($filePath, $content);
        }
    }

    /**
     * Update the composer.json and run the Composer.
     *
     * @param string $type
     * @return void
     */
    protected function updateComposerJson($path)
    {
        $helpers = 'shared/Support/helpers.php';

        //
        $composerJson = getenv('COMPOSER') ?: 'composer.json';

        $path = base_path($composerJson);

        // Get the composer.json contents in a decoded form.
        $config = json_decode(file_get_contents($path), true);

        if (! is_array($config)) {
            return;
        }

        // Update the composer.json
        else if (! in_array($helpers, $files = Arr::get($config, "autoload.files", array()))) {
            array_push($files, $helpers);

            Arr::set($config, "autoload.files", $files);

            $output = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

            file_put_contents($path, $output);
        }
    }
}
