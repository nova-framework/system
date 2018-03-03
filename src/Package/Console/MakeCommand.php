<?php

namespace Nova\Package\Console;

use Nova\Console\Command as CommandGenerator;
use Nova\Filesystem\Filesystem;
use Nova\Package\PackageManager;
use Nova\Support\Str;


class MakeCommand extends CommandGenerator
{
    /**
     * Package folders to be created.
     *
     * @var array
     */
    protected $listFolders = array();

    /**
     * Package files to be created.
     *
     * @var array
     */
    protected $listFiles = array();

    /**
     * Package signature option.
     *
     * @var array
     */
    protected $signOption = array();

    /**
     * Package stubs used to populate defined files.
     *
     * @var array
     */
    protected $listStubs = array();

    /**
     * The Packages instance.
     *
     * @var \Nova\Package\PackageManager
     */
    protected $packages;

    /**
     * The Packages path.
     *
     * @var string
     */
    protected $packagePath;

    /**
     * The Packages info.
     *
     * @var Nova\Support\Collection;
     */
    protected $packageInfo;

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
    protected $data;

    /**
     * String to store the command type.
     *
     * @var string
     */
    protected $type;


    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param \Nova\Package\PackageManager    $package
     */
    public function __construct(Filesystem $files, PackageManager $packages)
    {
        parent::__construct();

        //
        $this->files = $files;

        $this->packages = $packages;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $slug = $this->parseSlug($this->argument('slug'));
        $name = $this->parseName($this->argument('name'));

        if (! $this->packages->exists($slug)) {
            return $this->error('Package ['.$this->data['slug'].'] does not exist.');
        }

        $this->packageInfo = collect(
            $this->packages->where('slug', $slug)
        );

        // Check for a proper type of the
        $type = $this->packageInfo->get('type');

        if (($type != 'package') && ($type != 'module')) {
            return $this->error('Package [' .$this->data['slug'] .'] has no generator of this type.');
        }

        $this->packagesPath = ($type == 'module')
            ? $this->packages->getModulesPath()
            : $this->packages->getPackagesPath();

        $this->data['slug'] = $slug;
        $this->data['name'] = $name;

        return $this->generate();
    }

    /**
     * generate the console command.
     *
     * @return mixed
     */
    protected function generate()
    {
        foreach ($this->listFiles as $key => $file) {
            $filePath = $this->makeFilePath($this->listFolders[$key], $this->data['name']);

            $this->resolveByPath($filePath);

            $file = $this->formatContent($file);

            //
            $find = basename($filePath);

            $filePath = strrev(preg_replace(strrev("/$find/"), '', strrev($filePath), 1));

            $filePath = $filePath .$file;

            if ($this->files->exists($filePath)) {
                return $this->error($this->type .' already exists!');
            }

            $this->makeDirectory($filePath);

            foreach ($this->signOption as $option) {
                if ($this->option($option)) {
                    $stubFile = $this->listStubs[$option][$key];

                    $this->resolveByOption($this->option($option));

                    break;
                }
            }

            if (! isset($stubFile)) {
                $stubFile = $this->listStubs['default'][$key];
            }

            $this->files->put($filePath, $this->getStubContent($stubFile));
        }

        return $this->info($this->type.' created successfully.');
    }

    /**
     * Resolve Container after getting file path.
     *
     * @param string $FilePath
     *
     * @return array
     */
    protected function resolveByPath($filePath)
    {
        //
    }

    /**
     * Resolve Container after getting input option.
     *
     * @param string $option
     *
     * @return array
     */
    protected function resolveByOption($option)
    {
        //
    }

    /**
     * Parse slug name of the Package.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function parseSlug($slug)
    {
        return Str::snake($slug);
    }

    /**
     * Parse class name of the Package.
     *
     * @param string $slug
     *
     * @return string
     */
    protected function parseName($name)
    {
        if (str_contains($name, '\\')) {
            $name = str_replace('\\', '/', $name);
        }

        if (str_contains($name, '/')) {
            $formats = collect(explode('/', $name))->map(function ($name)
            {
                return Str::studly($name);
            });

            $name = $formats->implode('/');
        } else {
            $name = Str::studly($name);
        }

        return $name;
    }

    /**
     * Make FilePath.
     *
     * @param string $folder
     * @param string $name
     *
     * @return string
     */
    protected function makeFilePath($folder, $name)
    {
        $folder = ltrim($folder, '\/');
        $folder = rtrim($folder, '\/');

        $name = ltrim($name, '\/');
        $name = rtrim($name, '\/');

        if ($this->packageInfo->get('type') == 'module') {
            return $this->packagesPath .DS .$this->packageInfo->get('basename') .DS .$folder .DS .$name;
        }

        return $this->packagesPath .DS .$this->packageInfo->get('basename') .DS .'src' .DS .$folder .DS .$name;
    }

    /**
     * Make FileName.
     *
     * @param string $filePath
     *
     * @return string
     */
    protected function makeFileName($filePath)
    {
        return basename($filePath);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param string $path
     *
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Get Namespace of the current file.
     *
     * @param string $file
     *
     * @return string
     */
    protected function getNamespace($file)
    {
        $basename = $this->packageInfo->get('basename');

        if ($this->packageInfo->get('type') == 'module') {
            $namespace = str_replace($this->packagesPath .DS .$basename, '', $file);
        } else {
            $namespace = str_replace($this->packagesPath .DS .$basename .DS .'src', '', $file);
        }

        $find = basename($namespace);

        $namespace = strrev(preg_replace(strrev("/$find/"), '', strrev($namespace), 1));

        $namespace = $this->packageInfo->get('namespace') .'\\' .trim($namespace, '\\/');

        return str_replace('/', '\\', $namespace);
    }

    /**
     * Get the configured Package base namespace.
     *
     * @return string
     */
    protected function getBaseNamespace()
    {
        if ($this->packageInfo->get('type') == 'module') {
            return $this->packages->getModulesNamespace();
        }

        return $this->packages->getPackagesNamespace();
    }

    /**
     * Get stub content by key.
     *
     * @param int $key
     *
     * @return string
     */
    protected function getStubContent($stubName)
    {
        $stubPath = $this->getStubsPath() .$stubName;

        $content = $this->files->get($stubPath);

        return $this->formatContent($content);
    }

    /**
     * Get stubs path.
     *
     * @return string
     */
    protected function getStubsPath()
    {
        return dirname(__FILE__) .DS .'stubs' .DS;
    }

    /**
     * Replace placeholder text with correct values.
     *
     * @return string
     */
    protected function formatContent($content)
    {
        //
    }
}
