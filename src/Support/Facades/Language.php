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
        $language = Config::get['config']['app.locale'];

        if (Session::has('language')) {
            $language = Session::get('language', $language);
        } else if(Cookie::has(PREFIX .'language')) {
            $language = Cookie::get(PREFIX .'language', $language);

            Session::set('language', $language);
        }

        static::$app['language']->setLocale($language);
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'language'; }
}
