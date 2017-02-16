<?php

namespace Nova\Plugin\Generators;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Plugin\PluginManager;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;


class MakePluginCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:plugin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Plugin';

    /**
     * Plugin folders to be created.
     *
     * @var array
     */
    protected $pluginFolders = array(
        'Config/',
        'Language/',
        'Providers/',
    );

    /**
     * Plugin files to be created.
     *
     * @var array
     */
    protected $pluginFiles = array(
        'Config/Config.php',
        'Providers/PluginServiceProvider.php',
        'README.md',
    );

    /**
     * Plugin stubs used to populate defined files.
     *
     * @var array
     */
    protected $pluginStubs = array(
        'config',
        'plugin-service-provider',
        'readme',
    );

    /**
     * The plugins instance.
     *
     * @var \Nova\Plugin\PluginManager
     */
    protected $plugin;

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Array to store the configuration details.
     *
     * @var array
     */
    protected $container = array();

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param \>Nova\Plugin\PluginManager    $plugin
     */
    public function __construct(Filesystem $files, PluginManager $plugin)
    {
        parent::__construct();

        $this->files  = $files;

        $this->plugin = $plugin;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $name = $this->argument('name');

        if (Str::length($name) > 3) {
            $slug = Str::snake($name);
        } else {
            $slug = Str::lower($name);
        }

        $this->container['slug'] = $slug;

        //
        $name = (Str::length($slug) > 3) ? Str::studly($slug) : Str::upper($slug);

        $this->container['name']      = $name;
        $this->container['namespace'] = $name;

        if ($this->option('quick')) {
            return $this->generate();
        }

        $this->stepOne();
    }


    /**
     * Step 1: Configure plugin.
     *
     * @return mixed
     */
    private function stepOne()
    {
        $this->container['name'] = $this->ask('Please enter the name of the plugin:', $this->container['name']);

        $this->comment('You have provided the following information:');

        $this->comment('Name:        '.$this->container['name']);

        if ($this->confirm('Do you wish to continue?')) {
            $this->generate();
        } else {
            return $this->stepOne();
        }

        return true;
    }

    /**
     * Generate the plugin.
     */
    protected function generate()
    {
        $steps = array(
            'Generating folders...'      => 'generateFolders',
            'Generating files...'        => 'generateFiles',
            'Generating .gitkeep...'     => 'generateGitkeep',
        );

        $progress = new ProgressBar($this->output, count($steps));

        $progress->start();

        foreach ($steps as $message => $function) {
            $progress->setMessage($message);

            $this->$function();

            $progress->advance();
        }

        $progress->finish();

        $this->info("\nPlugin generated successfully.");
    }

    /**
     * Generate defined plugin folders.
     */
    protected function generateFolders()
    {
        $slug = $this->container['slug'];

        //
        $path = $this->plugin->getPath();

        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path);
        }

        $path = $this->getPluginPath($slug, true);

        $this->files->makeDirectory($path);

        //
        $pluginPath = $this->getPluginPath($slug);

        // Generate the Plugin directories.
        foreach ($this->pluginFolders as $folder) {
            $path = $pluginPath .$folder;

            $this->files->makeDirectory($path);
        }

        // Generate the Language inner directories.
        $languageFolders = $this->getLanguagePaths($slug);

        foreach ($languageFolders as $folder) {
            $path = $pluginPath .$folder;

            $this->files->makeDirectory($path);
        }
    }

    /**
     * Generate defined plugin files.
     */
    protected function generateFiles()
    {
        foreach ($this->pluginFiles as $key => $file) {
            $file = $this->formatContent($file);

            $this->files->put($this->getDestinationFile($file), $this->getStubContent($key));
        }

        // Generate the Language files
        $slug = $this->container['slug'];

        $pluginPath = $this->getPluginPath($slug);

        $content ='<?php

return array (
);';

        $languageFolders = $this->getLanguagePaths($slug);

        foreach ($languageFolders as $folder) {
            $path = $pluginPath .$folder .DS .'messages.php';

            $this->files->put($path, $content);
        }
    }

    /**
     * Generate .gitkeep files within generated folders.
     */
    protected function generateGitkeep()
    {
        $slug = $this->container['slug'];

        $pluginPath = $this->getPluginPath($slug);

        foreach ($this->pluginFolders as $folder) {
            $path = $pluginPath .$folder;

            //
            $files = $this->files->glob($path .'/*');

            if(! empty($files)) continue;

            $gitkeep = $path .'/.gitkeep';

            $this->files->put($gitkeep, '');
        }
    }

    /**
     * Get the path to the plugin.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function getPluginPath($slug = null)
    {
        if (! is_null($slug)) {
            return $this->plugin->getPluginPath($slug);
        }

        return $this->plugin->getPath();
    }

    protected function getLanguagePaths($slug)
    {
        $paths = array();

        $languages = $this->nova['config']['languages'];

        foreach (array_keys($languages) as $code) {
            $paths[] = 'Language' .DS .strtoupper($code);
        }

        return $paths;
    }

    /**
     * Get destination file.
     *
     * @param string $file
     *
     * @return string
     */
    protected function getDestinationFile($file)
    {
        return $this->getPluginPath($this->container['slug']) .$this->formatContent($file);
    }

    /**
     * Get stub content by key.
     *
     * @param int $key
     *
     * @return string
     */
    protected function getStubContent($key)
    {
        $stub = $this->pluginStubs[$key];

        $path = __DIR__ .DS .'stubs' .DS .$stub .'.stub';

        $content = $this->files->get($path);

        return $this->formatContent($content);
    }

    /**
     * Replace placeholder text with correct values.
     *
     * @return string
     */
    protected function formatContent($content)
    {
        $searches = array(
            '{{slug}}',
            '{{name}}',
            '{{namespace}}',
            '{{path}}'
        );

        $replaces = array(
            $this->container['slug'],
            $this->container['name'],
            $this->container['namespace'],
            $this->plugin->getNamespace(),
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
            array('name', InputArgument::REQUIRED, 'The name of the Plugin.'),
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
            array('--quick', '-Q', InputOption::VALUE_REQUIRED, 'Skip the make:plugin Wizard and use default values'),
        );
    }
}
