<?php

namespace Nova\Package\Console;

use Nova\Config\Repository as Config;
use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Package\PackageManager;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;


class ThemeMakeCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:theme';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Theme';

    /**
     * Plugin folders to be created.
     *
     * @var array
     */
    protected $themeFolders = array(
        'Assets/',
        'Assets/css/',
        'Assets/images/',
        'Assets/js/',
        'Config/',
        'Language/',
        'Overrides/',
        'Overrides/Packages/',
        'Providers/',
        'Views/',
        'Views/Layouts/',
        'Views/Layouts/RTL',
    );

    /**
     * Plugin files to be created.
     *
     * @var array
     */
    protected $themeFiles = array(
        'Assets/css/style.css',
        'Config/Config.php',
        'Providers/ThemeServiceProvider.php',
        'Views/Layouts/Default.php',
        'Views/Layouts/RTL/Default.php',
        'Bootstrap.php',
        'README.md',
    );

    /**
     * Plugin stubs used to populate defined files.
     *
     * @var array
     */
    protected $themeStubs = array(
        'style',
        'config',
        'theme-service-provider',
        'layout',
        'layout',
        'theme-bootstrap',
        'readme',
    );

    /**
     * The Config Repository instance.
     *
     * @var \Nova\Config\Repository
     */
    protected $config;

    /**
     * The Packages Manager instance.
     *
     * @var \Nova\Package\PackageManager
     */
    protected $packages;

    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Array to store the configuration details.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param \Nova\Config\Repository    $config
     * @param \Nova\Package\PackageManager    $packages
     */
    public function __construct(Filesystem $files, Config $config, PackageManager $packages)
    {
        parent::__construct();

        //
        $this->files  = $files;
        $this->config = $config;
        $this->packages = $packages;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $namespace = $this->packages->getThemesNamespace();

        $name = $this->argument('name');

        if (Str::length($name) > 3) {
            $slug = Str::snake($name);
        } else {
            $slug = Str::lower($name);
        }

        $this->data['slug'] = $slug;

        //
        $name = (Str::length($slug) > 3) ? Str::studly($slug) : Str::upper($slug);

        $this->data['name'] = $name;

        $this->data['namespace'] =  $namespace .'\\' .$name;

        if ($this->option('quick')) {
            return $this->generate();
        }

        $this->stepOne();
    }


    /**
     * Step 1: Configure theme.
     *
     * @return mixed
     */
    private function stepOne()
    {
        $namespace = $this->packages->getThemesNamespace();

        $this->data['name'] = $this->ask('Please enter the name of the Theme:', $this->data['name']);
        $this->data['slug'] = $this->ask('Please enter the slug of the Theme:', $this->data['slug']);

        //
        $this->data['namespace'] = $namespace .'\\' .$this->data['name'];

        $this->comment('You have provided the following information:');

        $this->comment('Name:       ' .$this->data['name']);
        $this->comment('Slug:       ' .$this->data['slug']);

        if ($this->confirm('Do you wish to continue?')) {
            $this->generate();
        } else {
            return $this->stepOne();
        }

        return true;
    }

    /**
     * Generate the theme.
     */
    protected function generate()
    {
        $slug = $this->data['slug'];

        if ($this->files->exists($this->getThemePath($slug))) {
            $this->error('The Theme [' .$slug .'] already exists!');

            return false;
        }

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

        $this->info("\nGenerating optimized class loader");

        $this->container['composer']->dumpOptimized();

        $this->info("\nTheme generated successfully.");
    }

    /**
     * Generate defined theme folders.
     */
    protected function generateFolders()
    {
        $slug = $this->data['slug'];

        //
        $path = $this->getThemePath();

        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path);
        }

        $path = $this->getThemePath($slug);

        $this->files->makeDirectory($path);

        //
        $themePath = $this->getThemePath($slug);

        // Generate the Plugin directories.
        foreach ($this->themeFolders as $folder) {
            $path = $themePath .$folder;

            $this->files->makeDirectory($path);
        }

        // Generate the Language inner directories.
        $languageFolders = $this->getLanguagePaths($slug);

        foreach ($languageFolders as $folder) {
            $path = $themePath .$folder;

            $this->files->makeDirectory($path);
        }
    }

    /**
     * Generate defined theme files.
     */
    protected function generateFiles()
    {
        foreach ($this->themeFiles as $key => $file) {
            $file = $this->formatContent($file);

            $this->files->put($this->getDestinationFile($file), $this->getStubContent($key));
        }

        // Generate the Language files
        $slug = $this->data['slug'];

        $themePath = $this->getThemePath($slug);

        $content ='<?php

return array (
);';

        $languageFolders = $this->getLanguagePaths($slug);

        foreach ($languageFolders as $folder) {
            $path = $themePath .$folder .DS .'messages.php';

            $this->files->put($path, $content);
        }
    }

    /**
     * Generate .gitkeep files within generated folders.
     */
    protected function generateGitkeep()
    {
        $slug = $this->data['slug'];

        $themePath = $this->getThemePath($slug);

        foreach ($this->themeFolders as $folder) {
            $path = $themePath .$folder;

            //
            $files = $this->files->glob($path .'/*');

            if(! empty($files)) continue;

            $gitkeep = $path .'/.gitkeep';

            $this->files->put($gitkeep, '');
        }
    }

    /**
     * Get the path to the theme.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function getThemePath($slug = null)
    {
        $themesPath = $this->packages->getThemesPath();

        if (is_null($slug)) {
            return $themesPath;
        }

        if (Str::length($slug) > 3) {
            $name = Str::studly($slug);
        } else {
            $name = Str::upper($slug);
        }

        return $themesPath .DS .$name .DS;
    }

    protected function getLanguagePaths($slug)
    {
        $paths = array();

        $languages = $this->config->get('languages', array());

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
        return $this->getThemePath($this->data['slug']) .$this->formatContent($file);
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
        $stub = $this->themeStubs[$key];

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
            '{{type}}',
            '{{lower_type}}',

            //
            '{{slug}}',
            '{{name}}',
            '{{namespace}}',
        );

        $replaces = array(
            'Theme',
            'theme',

            //
            $this->data['slug'],
            $this->data['name'],
            $this->data['namespace'],
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
            array('--quick', '-Q', InputOption::VALUE_NONE, 'Skip the make:theme Wizard and use default values'),
        );
    }
}
