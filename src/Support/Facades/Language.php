<?php
/**
 * Language - A Facade to the Language.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Support\Facades;

use Nova\Language\Language as CoreLanguage;

use Nova\Support\Facades\Facade;
use Nova\Support\Facades\Config;
use Nova\Support\Facades\Cookie;
use Nova\Support\Facades\Session;

use ReflectionMethod;
use ReflectionException;


class Language extends Facade
{
    public static function initialize()
    {
        $languages = Config::get('languages');

        if (Session::has('language')) {
            // The Language was already set; nothing to do.
            return;
        } else if(Cookie::has(PREFIX .'language')) {
            $cookie = Cookie::get(PREFIX .'language');

            if (preg_match ('/[a-z]/', $cookie) && in_array($cookie, array_keys($languages))) {
                Session::set('language', $cookie);
            }
        }
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'language'; }
}
