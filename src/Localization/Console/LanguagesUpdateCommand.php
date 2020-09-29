<?php

namespace Nova\Localization\Console;

use Nova\Config\Repository as Config;
use Nova\Console\Command;
use Nova\Filesystem\FileNotFoundException;
use Nova\Filesystem\Filesystem;
use Nova\Localization\LanguageManager;
use Nova\Support\Arr;
use Nova\Support\Str;

use Symfony\Component\Console\Input\InputOption;

use Exception;
use Throwable;


class LanguagesUpdateCommand extends Command
{
    /**
     * The name of the console command.
     *
     * @var string
     */
    protected $name = 'language:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the Language files';

    /**
     * The Language Manager instance.
     *
     * @var LanguageManager
     */
    protected $languages;

    /**
     * The Filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * @var string
     */
    protected static $appPattern = '#__\(\'(.*)\'(?:,.*)?\)#smU';

    /**
     * @var string
     */
    protected static $pattern = '#__d\(\'(?:.*)?\',.?\s?\'(.*)\'(?:,.*)?\)#smU';


    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(LanguageManager $languages, Filesystem $files)
    {
        parent::__construct();

        //
        $this->languages = $languages;

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = $this->container['config'];

        // Get the Language codes.
        $languages = array_keys(
            $config->get('languages', array())
        );

        if (! empty($path = $this->option('path'))) {
            $paths = (array) $path;
        }

        // A custom path is not specified.
        else {
            $paths = $this->scanWorkPaths($config);
        }

        //
        // Update the Language files in the available Domains.

        foreach ($paths as $path) {
            $this->processPath($path, $languages);
        }
    }

    protected function processPath($path, array $languages)
    {
        if (! $this->files->isDirectory($path)) {
            $this->error(PHP_EOL .'Not a directory: "' .$path .'"');
        }

        //
        else if (! $this->files->isDirectory($path .DS .'Language')) {
            $this->comment(PHP_EOL .'Not a translatable path: "' .$path .'"');
        }

        //
        else {
            $this->updateLanguageFiles($path, $languages);
        }
    }

    protected function scanWorkPaths(Config $config)
    {
        $results = array(
            app_path(),
            base_path('shared')
        );

        // Search for the Modules and Themes.
        $paths = array(
            $config->get('packages.modules.path', BASEPATH .'modules'),
            $config->get('packages.themes.path', BASEPATH .'themes')
        );

        foreach ($paths as $path) {
            if ($this->files->isDirectory($path)) {
                $directories = $this->files->glob($path .'/*', GLOB_ONLYDIR);

                $results = array_merge($results, $directories);
            }
        }

        // Search for the local Packages.
        $path = BASEPATH .'packages';

        if (! $this->files->isDirectory($path)) {
            return $results;
        }

        return array_merge($results, array_map(function ($path)
        {
            return $path .DS .'src';

        }, $this->files->glob($path .'/*', GLOB_ONLYDIR)));
    }

    protected function updateLanguageFiles($path, $languages)
    {
        $withoutDomain = ($path == app_path());

        $pattern = $withoutDomain ? "__('" : "__d('";

        if (empty($paths = $this->fileGrep($pattern, $path))) {
            $this->comment(PHP_EOL .'No messages found in path: "' .$path .'"');

            return;
        }

        //
        else if (empty($messages = $this->extractMessages($paths, $withoutDomain))) {
            return;
        }

        $this->info(PHP_EOL .'Processing the messages found in path: "' .$path .'"');

        foreach ($languages as $language) {
            $this->updateLanguageFile($language, $path, array_unique($messages));
        }
    }

    protected function fileGrep($pattern, $path)
    {
        $results = array();

        $fp = opendir($path);

        while ($fileName = readdir($fp)) {
            if (preg_match("#^\.+$#", $fileName) === 1) {
                // Ignore symbolic links.
                continue;
            }

            $fullPath = $path .DS .$fileName;

            if ($this->files->isDirectory($fullPath)) {
                $results = array_merge($results, $this->fileGrep($pattern, $fullPath));
            }

            // The current path is not a directory.
            else if (preg_match("#^(.*)\.(php|tpl)$#", $fileName) !== 1) {
                continue;
            }

            // We found a PHP or TPL file.
            else if (stristr(file_get_contents($fullPath), $pattern)) {
                $results[] = $fullPath;
            }
        }

        return array_unique($results);
    }

    protected function extractMessages(array $paths, $withoutDomain)
    {
        if ($withoutDomain) {
            $pattern = '#__\(\'(.*)\'(?:,.*)?\)#smU';
        } else {
            $pattern = '#__d\(\'(?:.*)?\',.?\s?\'(.*)\'(?:,.*)?\)#smU';
        }

        //$this->comment("Using the PATERN: '" .$pattern ."'");

        // Extract the messages from files and return them.
        $results = array();

        foreach ($paths as $path) {
            $content = $this->getFileContents($path);

            if (preg_match_all($pattern, $content, $matches) === false) {
                continue;
            } else if (empty($messages = $matches[1])) {
                continue;
            }

            foreach ($messages as $message) {
                //$message = trim($message);

                if (($message == '$message, $args = null') || ($message == '$domain, $message, $args = null')) {
                    // We will skip the translation functions definition.
                    continue;
                }

                $results[] = str_replace("\\'", "'", $message);
            }
        }

        return $results;
    }

    protected function getFileContents($path)
    {
        try {
            return $this->files->get($path);
        }
        catch (Exception | Throwable $e) {
            //
        }

        return '';
    }

    protected function updateLanguageFile($language, $path, array $messages)
    {
        $path = $path .str_replace('/', DS, '/Language/' .strtoupper($language) .'/messages.php');

        $data = $this->getMessagesFromFile($path);

        //
        $result = array();

        foreach ($messages as $key) {
            $result[$key] = Arr::get($data, $key, '');
        }

        $this->writeLanguageFile($path, $result);

        $this->line('Written the Language file: "' .str_replace(BASEPATH, '', $path) .'"');
    }

    protected function getMessagesFromFile($path)
    {
        $data = array();

        try {
            $data = $this->files->getRequire($path);
        }
        catch (Exception | Throwable $e) {
            //
        }

        return is_array($data) ? $data : array();
    }

    protected function writeLanguageFile($path, array $data)
    {
        ksort($data);

        // Make sure that the directory exists.
        $this->files->makeDirectory(dirname($path), 0755, true, true);

        // Prepare the Language file contents.
        $output = "<?php\n\nreturn " .var_export($data, true) .";\n";

        //$output = preg_replace("/^ {2}(.*)$/m","    $1", $output);

        $this->files->put($path, $output);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('path', null, InputOption::VALUE_REQUIRED, 'Indicates the path from where translation strings are processed.'),
        );
    }
}
