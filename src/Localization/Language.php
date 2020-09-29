<?php

namespace Nova\Localization;

use Nova\Filesystem\Filesystem;
use Nova\Localization\LanguageManager;
use Nova\Localization\MessageFormatter;
use Nova\Support\Facades\Log;
use Nova\Support\Arr;

use Exception;


/**
 * A Language class to load the requested language file.
 */
class Language
{
    /**
     * The Language Manager Instance.
     *
     * @var \Nova\Localization\LanguageManager
     */
    protected $manager;

    /**
     * The Language Manager Instance.
     *
     * @var \Nova\Localization\LanguageManager
     */
    protected $files;

    /**
     * Holds an array with the Domain's Messages.
     *
     * @var array
     */
    private $messages = array();

    /**
     * The current Language Domain.
     */
    private $domain = null;

    /**
     * The current Language information.
     */
    private $code      = 'en';
    private $info      = 'English';
    private $name      = 'English';
    private $locale    = 'en-US';
    private $direction = 'ltr';

    /**
     * The current Language Domain.
     */
    private $path;


    /**
     * Create an new Language instance.
     *
     * @param \Nova\Localization\LanguageManager $manager
     * @param \Nova\Filesystem\Filesystem $files
     * @param string $domain
     * @param string $code
     * @param string $path
     * @param array  $data
     */
    public function __construct(LanguageManager $manager, Filesystem $files, $domain, $code, $path, array $data)
    {
        $this->manager = $manager;
        $this->files   = $files;
        $this->domain  = $domain;
        $this->code    = $code;

        // Setup the path to the translation files.
        $this->path = $path  .DS .strtoupper($code);

        // Extract the data and setup the associated parameters.
        extract($data);

        $this->info      = $info;
        $this->name      = $name;
        $this->locale    = $locale;
        $this->direction = $dir;

        // Finally, load the translation messages.
        $this->loadMessages();
    }

    /**
     * Load the messages from the translation file.
     * @return void
     */
    protected function loadMessages()
    {
        $path = str_replace('/', DS, sprintf('%s/messages.php', $this->path()));

        try {
            $messages = $this->files->getRequire($path);
        }
        catch (Exception | Throwable $e) {
            $messages = array();
        }

        // Some consistency check of the messages, before setting them.
        if (is_array($messages) && ! empty($messages)) {
            $this->messages = $messages;
        }
    }

    /**
     * Translate a message with optional formatting
     * @param string $message Original message.
     * @param array $parameters Optional params for formatting.
     * @param string|null $group The messages group
     * @return string
     */
    public function translate($message, array $parameters = array(), $group = null)
    {
        $translation = $this->translateMessage($message, $group);

        if (! empty($parameters)) {
            return with(new MessageFormatter())->format($translation, $parameters, $this->locale);
        }

        return $translation;
    }

    /**
     * Translate a message
     * @param string $message Original message.
     * @param string|null $group The messages group
     * @return string
     */
    protected function translateMessage($message, $group = null)
    {
        if (isset($this->messages[$message]) && ! empty($this->messages[$message])) {
            return $this->messages[$message];
        }

        return $message;
    }

    // Public Getters

    /**
     * Get current file path
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Get current domain
     * @return string
     */
    public function domain()
    {
        return $this->domain;
    }

    /**
     * Get current code
     * @return string
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * Get current info
     * @return string
     */
    public function info()
    {
        return $this->info;
    }

    /**
     * Get current name
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get current locale
     * @return string
     */
    public function locale()
    {
        return $this->locale;
    }

    /**
     * Get all messages
     * @return array
     */
    public function messages()
    {
        return $this->messages;
    }

    /**
     * Get the current direction
     *
     * @return string rtl or ltr
     */
    public function direction()
    {
        return $this->direction;
    }

}
