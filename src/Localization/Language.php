<?php

namespace Nova\Localization;

use Nova\Localization\LanguageManager;
use Nova\Localization\MessageFormatter;
use Nova\Support\Facades\Log;

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
     * Create an new Language instance.
     *
     * @param string $domain
     * @param string $code
     * @param string $path
     */
    public function __construct(LanguageManager $manager, $domain, $code)
    {
        $languages = $manager->getLanguages();

        if (isset($languages[$code]) && ! empty($languages[$code])) {
            $info = $languages[$code];

            $this->code = $code;

            //
            $this->info      = $info['info'];
            $this->name      = $info['name'];
            $this->locale    = $info['locale'];
            $this->direction = $info['dir'];
        } else {
            $code = 'en';
        }

        $this->domain = $domain;

        // Determine the current Language file path.
        $namespaces = $manager->getNamespaces();

        // Check if the language file is readable.
        if (! array_key_exists($domain, $namespaces)) return;

        // Determine the Language(s) path.
        $namespace = $namespaces[$domain];

        $filePath = $namespace .DS .strtoupper($code) .DS .'messages.php';

        if (is_readable($filePath)) {
            // The requested language file exists; retrieve the messages from it.
            $messages = require $filePath;

            // Some consistency check of the messages, before setting them.
            if (is_array($messages) && ! empty($messages)) {
                $this->messages = $messages;
            }
        }
    }

    /**
     * Translate a message with optional formatting
     * @param string $message Original message.
     * @param array $params Optional params for formatting.
     * @return string
     */
    public function translate($message, array $params = array())
    {
        // Update the current message with the domain translation, if we have one.
        if (isset($this->messages[$message]) && ! empty($this->messages[$message])) {
            $message = $this->messages[$message];
        }

        if (empty($params)) {
            return $message;
        }

        $formatter = new MessageFormatter();

        return $formatter->format($message, $params, $this->locale);
    }

    // Public Getters

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
