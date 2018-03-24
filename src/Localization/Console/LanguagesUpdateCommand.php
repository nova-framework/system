<?php

namespace Nova\Localization\Console;

use Nova\Console\Command;
use Nova\Filesystem\Filesystem;
use Nova\Localization\LanguageManager;
use Nova\Support\Arr;
use Nova\Support\Str;


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

        //
        $languages = array_keys(
            $config->get('languages', array())
        );

        $workPaths = array_map(function ($path)
        {
            return str_replace(BASEPATH, '', $path);

        }, array(app_path(), base_path('shared')));

        // Search for Modules.
        $path = $config->get('packages.modules.path', BASEPATH .'modules');

        if ($this->files->isDirectory($path)) {
            $paths = $this->files->glob($path .'/*', GLOB_ONLYDIR);

            $workPaths = array_merge($workPaths, array_map(function ($path)
            {
                return str_replace(BASEPATH, '', $path);

            }, $paths));
        }

        // Search for Themes.
        $path = $config->get('packages.themes.path', BASEPATH .'themes');

        if ($this->files->isDirectory($path)) {
            $paths = $this->files->glob($path .'/*', GLOB_ONLYDIR);

            $workPaths = array_merge($workPaths, array_map(function ($path)
            {
                return str_replace(BASEPATH, '', $path);

            }, $paths));
        }

        // Search for Packages.
        $path = BASEPATH .'packages';

        if ($this->files->isDirectory($path)) {
            $paths = $this->files->glob($path .'/*', GLOB_ONLYDIR);

            $workPaths = array_merge($workPaths, array_map(function ($path)
            {
                return str_replace(BASEPATH, '', $path .DS .'src');

            }, $paths));
        }

        foreach($workPaths as $workPath) {
            $path = base_path($workPath);

            if (! $this->files->isDirectory($path)) {
                continue;
            }

            $this->updateLanguageFiles($path, $languages);
        }
    }

    protected function updateLanguageFiles($workPath, $languages)
    {
        $default = ($workPath == app_path());

        $pattern = $default ? "__('" : "__d('";

        if (empty($results = $this->fileGrep($pattern, $workPath))) {
            return;
        }

        if ($default) {
            $pattern = '#__\(\'(.*)\'(?:,.*)?\)#smU';
        } else {
            $pattern = '#__d\(\'(?:.*)?\',.?\s?\'(.*)\'(?:,.*)?\)#smU';
        }

        //$this->comment("Using PATERN: '" .$pattern."'");

        // Process the messages.
        $messages = array();

        foreach($results as $key => $filePath) {
            $content = file_get_contents($filePath);

            if (preg_match_all($pattern, $content, $matches)) {
                foreach($matches[1] as $message) {
                    //$message = trim($message);

                    if ($message == '$msg, $args = null') {
                        // This is the function
                        continue;
                    }

                    $messages[] = str_replace("\\'", "'", $message);
                }
            }
        }

        if (! empty($messages)) {
            $this->info(PHP_EOL .'Messages found on path "'.$workPath.'". Processing...');

            $messages = array_flip($messages);

            foreach($languages as $language) {
                $langFile = $workPath .'/Language/'.strtoupper($language).'/messages.php';

                if (is_readable($langFile)) {
                    $oldData = include($langFile);

                    $oldData = is_array($oldData) ? $oldData : array();
                } else {
                    $oldData = array();
                }

                foreach($messages as $message => $value) {
                    if (! array_key_exists($message, $oldData)) {
                        $messages[$message] = '';
                    } else {
                        $value = $oldData[$message];

                        if (!empty($value) && is_string($value)) {
                            $messages[$message] = $value;
                        } else {
                            $messages[$message] = '';
                        }
                    }
                }

                ksort($messages);

                $output = "<?php

return " .var_export($messages, true).";\n";

                //$output = preg_replace("/^ {2}(.*)$/m","    $1", $output);

                file_put_contents($langFile, $output);

                $this->line('Written the Language file: "'.str_replace(BASEPATH, '', $langFile).'"');
            }
        }
    }

    protected function fileGrep($pattern, $path) {
        $result = array();

        $fp = opendir($path);

        while($f = readdir($fp)) {
            if (preg_match("#^\.+$#", $f) === 1) continue; // ignore symbolic links

            $fullPath = $path .DS .$f;

            if ($this->files->isDirectory($fullPath)) {
                $result = array_unique(array_merge($result, $this->fileGrep($pattern, $fullPath)));
            }
            else if(stristr(file_get_contents($fullPath), $pattern)) {
                $result[] = $fullPath;
            }
        }

        return $result;
    }
}
