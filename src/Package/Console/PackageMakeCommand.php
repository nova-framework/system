<?php

namespace Nova\Package\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Package\PackageManager;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;


class PackageMakeCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'make:package';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Package';

    /**
     * Package folders to be created.
     *
     * @var array
     */
    protected $packageFolders = array(
        'default' => array(
            'assets/',
            'src/',
            'src/Config/',
            'src/Database/',
            'src/Database/Migrations/',
            'src/Database/Seeds/',
            'src/Language/',
            'src/Providers/',
        ),
        'extended' => array(
            'assets/',
            'src/',
            'src/Config/',
            'src/Controllers/',
            'src/Database/',
            'src/Database/Migrations/',
            'src/Database/Seeds/',
            'src/Events/',
            'src/Language/',
            'src/Listeners/',
            'src/Models/',
            'src/Policies/',
            'src/Providers/',
            'src/Routes/',
            'src/Views/',
        )
    );

    /**
     * Package files to be created.
     *
     * @var array
     */
    protected $packageFiles = array(
        'default' => array(
            'src/Config/Config.php',
            'src/Database/Seeds/DatabaseSeeder.php',
            'src/Providers/PackageServiceProvider.php',
            'README.md',
            'composer.json'
        ),
        'extended' => array(
            'src/Config/Config.php',
            'src/Database/Seeds/DatabaseSeeder.php',
            'src/Providers/AuthServiceProvider.php',
            'src/Providers/EventServiceProvider.php',
            'src/Providers/PackageServiceProvider.php',
            'src/Providers/RouteServiceProvider.php',
            'src/Routes/Api.php',
            'src/Routes/Web.php',
            'src/Bootstrap.php',
            'src/Events.php',
            'README.md',
            'composer.json'
        )
    );

    /**
     * Package stubs used to populate defined files.
     *
     * @var array
     */
    protected $packageStubs = array(
        'default' => array(
            'config',
            'seeder',
            'standard-service-provider',
            'readme',
            'composer'
        ),
        'extended' => array(
            'config',
            'seeder',
            'auth-service-provider',
            'event-service-provider',
            'extended-service-provider',
            'route-service-provider',
            'api-routes',
            'web-routes',
            'bootstrap',
            'events',
            'readme',
            'composer'
        )
    );

    /**
     * The Packages instance.
     *
     * @var \Nova\Package\PackageManager
     */
    protected $packages;

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
    protected $data = array();


    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param \Nova\Package\PackageManager    $packages
     */
    public function __construct(Filesystem $files, PackageManager $packages)
    {
        parent::__construct();

        $this->files  = $files;

        $this->packages = $package;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        if (strpos($name, '/') > 0) {
            list ($vendor, $name) = explode('/', $name);
        } else {
            $vendor = null;
        }

        if (Str::length($name) > 3) {
            $slug = Str::snake($name);
        } else {
            $slug = Str::lower($name);
        }

        $this->data['slug'] = $slug;

        //
        if (Str::length($slug) > 3) {
            $name = Str::studly($slug);
        } else {
            $name = Str::upper($slug);
        }

        $this->data['name'] = $name;

        //
        $otherSlug = str_replace('_', '-', $slug);

        if (! is_null($vendor)) {
            $package = Str::studly($vendor) .'/' .$name;

            $this->data['lower_package'] = Str::snake($vendor, '-') .'/' .$otherSlug;
        } else {
            $package = $name;

            $this->data['lower_package'] = 'acme-corp/' .$otherSlug;
        }

        $this->data['package'] = $package;

        $this->data['namespace'] = str_replace('/', '\\', $package);

        //
        $config = $this->container['config'];

        $this->data['author']   = $config->get('packages.author.name');
        $this->data['email']    = $config->get('packages.author.email');
        $this->data['homepage'] = $config->get('packages.author.homepage');

        $this->data['license'] = 'MIT';

        if ($this->option('quick')) {
            return $this->generate();
        }

        $this->stepOne();
    }

    /**
     * Step 1: Configure Package.
     *
     * @return mixed
     */
    private function stepOne()
    {
        $this->data['name'] = $this->ask('Please enter the name of the Package:', $this->data['name']);
        $this->data['slug'] = $this->ask('Please enter the slug of the Package:', $this->data['slug']);

        if (strpos($this->data['package'], '/') > 0) {
            list ($vendor) = explode('/', $this->data['package']);
        } else {
            $vendor = null;
        }

        $vendor = $this->ask('Please enter the vendor of the Package:', $vendor);

        //
        $slug = str_replace('_', '-', $this->data['slug']);

        if (! empty($vendor)) {
            $this->data['package'] = Str::studly($vendor) .'/' .$this->data['name'];

            $this->data['lower_package'] = Str::snake($vendor, '-') .'/' .$slug;
        } else {
            $this->data['package'] = $this->data['name'];

            $this->data['lower_package'] = 'acme-corp/' .$slug;
        }

        //
        $this->data['namespace'] = $this->ask('Please enter the namespace of the Package:', $this->data['namespace']);

        $this->data['license'] = $this->ask('Please enter the license of the Package:', $this->data['license']);

        $this->comment('You have provided the following information:');

        $this->comment('Name:       ' .$this->data['name']);
        $this->comment('Slug:       ' .$this->data['slug']);
        $this->comment('Package:    ' .$this->data['package']);
        $this->comment('Namespace:  ' .$this->data['namespace']);
        $this->comment('License:    ' .$this->data['license']);

        if ($this->confirm('Do you wish to continue?')) {
            $this->generate();
        } else {
            return $this->stepOne();
        }

        return true;
    }

    /**
     * Generate the Package.
     */
    protected function generate()
    {
        $slug = $this->data['slug'];

        if ($this->files->exists($this->getPackagePath($slug))) {
            $this->error('The Package [' .$slug .'] already exists!');

            return false;
        }

        $steps = array(
            'Generating folders...'          => 'generateFolders',
            'Generating files...'            => 'generateFiles',
            'Generating .gitkeep ...'        => 'generateGitkeep',
            'Updating the composer.json ...' => 'updateComposerJson',
        );

        $progress = new ProgressBar($this->output, count($steps));

        $progress->start();

        foreach ($steps as $message => $method) {
            $progress->setMessage($message);

            call_user_func(array($this, $method));

            $progress->advance();
        }

        $progress->finish();

        $this->info("\nGenerating optimized class loader");

        $this->container['composer']->dumpOptimized();

        $this->info("Package generated successfully.");
    }

    /**
     * Generate defined Package folders.
     */
    protected function generateFolders()
    {
        $slug = $this->data['slug'];

        //
        $path = $this->packages->getPath();

        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path);
        }

        $path = $this->getPackagePath($slug, true);

        $this->files->makeDirectory($path);

        //
        $packagePath = $this->getPackagePath($slug);

        // Generate the Package directories.
        $mode = $this->option('extended') ? 'extended' : 'default';

        $packageFolders = $this->packageFolders[$mode];

        foreach ($packageFolders as $folder) {
            $path = $packagePath .$folder;

            $this->files->makeDirectory($path);
        }

        // Generate the Language inner directories.
        $languageFolders = $this->getLanguagePaths($slug);

        foreach ($languageFolders as $folder) {
            $path = $packagePath .$folder;

            $this->files->makeDirectory($path);
        }
    }

    /**
     * Generate defined Package files.
     */
    protected function generateFiles()
    {
        $mode = $this->option('extended') ? 'extended' : 'default';

        $packageFiles = $this->packageFiles[$mode];

        foreach ($packageFiles as $key => $file) {
            $file = $this->formatContent($file);

            $this->files->put($this->getDestinationFile($file), $this->getStubContent($key, $mode));
        }

        // Generate the Language files
        $slug = $this->data['slug'];

        $packagePath = $this->getPackagePath($slug);

        $content ='<?php

return array (
);';

        $languageFolders = $this->getLanguagePaths($slug);

        foreach ($languageFolders as $folder) {
            $path = $packagePath .$folder .DS .'messages.php';

            $this->files->put($path, $content);
        }
    }

    /**
     * Generate .gitkeep files within generated folders.
     */
    protected function generateGitkeep()
    {
        $slug = $this->data['slug'];

        $packagePath = $this->getPackagePath($slug);

        //
        $mode = $this->option('extended') ? 'extended' : 'default';

        $packageFolders = $this->packageFolders[$mode];

        foreach ($packageFolders as $folder) {
            $path = $packagePath .$folder;

            //
            $files = $this->files->glob($path .'/*');

            if(! empty($files)) continue;

            $gitkeep = $path .DS .'.gitkeep';

            $this->files->put($gitkeep, '');
        }
    }

    /**
     * Update the composer.json and run the Composer.
     */
    protected function updateComposerJson()
    {
        $composerJson = getenv('COMPOSER') ?: 'composer.json';

        $path = base_path($composerJson);

        //
        $config = json_decode(file_get_contents($path), true);

        if (is_array($config) && isset($config['autoload'])) {
            $namespace = $this->data['namespace'] .'\\';

            $config['autoload']['psr-4'][$namespace] = 'packages/' . $this->data['name'] . "/src/";

            $output = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

            file_put_contents($path, $output);
        }
    }

    /**
     * Get the path to the Package.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function getPackagePath($slug = null)
    {
        if (! is_null($slug)) {
            return $this->packages->getPackagePath($slug);
        }

        return $this->packages->getPath();
    }

    protected function getLanguagePaths($slug)
    {
        $paths = array();

        $languages = $this->container['config']['languages'];

        foreach (array_keys($languages) as $code) {
            $paths[] = 'src' .DS .'Language' .DS .strtoupper($code);
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
        $slug = $this->data['slug'];

        return $this->getPackagePath($slug) .$this->formatContent($file);
    }

    /**
     * Get stub content by key.
     *
     * @param int $key
     * @param bool $mode
     *
     * @return string
     */
    protected function getStubContent($key, $mode)
    {
        $packageStubs = $this->packageStubs[$mode];

        //
        $stub = $packageStubs[$key];

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
            '{{package}}',
            '{{lower_package}}',
            '{{author}}',
            '{{email}}',
            '{{homepage}}',
            '{{license}}',
            '{{json_namespace}}',
        );

        $replaces = array(
            $this->data['slug'],
            $this->data['name'],
            $this->data['namespace'],
            $this->data['package'],
            $this->data['lower_package'],
            $this->data['author'],
            $this->data['email'],
            $this->data['homepage'],
            $this->data['license'],
            str_replace('/', '\\\\', $this->data['package'])
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
            array('name', InputArgument::REQUIRED, 'The name of the Package.'),
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
            array('--quick', '-Q', InputOption::VALUE_REQUIRED, 'Skip the make:package Wizard and use default values'),
            array('--extended', null, InputOption::VALUE_NONE, 'Generate an extended Package'),
        );
    }
}
