<?php
/**
 * URL Class.
 *
 * @author David Carr - dave@novaframework.com
 * @version 3.0
 */

namespace Nova\Helpers;

use Nova\Config\Config;
use Nova\Helpers\Inflector;


/**
 * Collection of methods for working with urls.
 */
class Url
{
    /**
     * Create the absolute address to the assets folder.
     *
     * @param  string|null $module
     * @return string url to assets folder
     */
    public static function resourcePath($module = null)
    {
        if ($module !== null) {
            $path = sprintf('modules/%s/', Inflector::tableize($module));
        } else {
            $path = '';
        }

        return Config::get('app.url') .$path .'assets/';
    }

    /**
     * Create the absolute address to the template folder.
     *
     * @param  boolean $custom
     * @return string url to template folder
     */
    public static function templatePath($custom = TEMPLATE, $folder = '/assets/')
    {
        $template = Inflector::tableize($custom);

        return Config::get('app.url') .'templates/' .$template .$folder;
    }

    /**
     * Create the relative address to the template folder.
     *
     * @param  boolean $custom
     * @return string path to template folder
     */
    public static function relativeTemplatePath($custom = TEMPLATE, $folder = '/Assets/')
    {
        return 'Templates/' .$custom .$folder;
    }

    /**
     * Converts plain text urls into HTML links, the second argument will be
     * used as the url label <a href=''>$custom</a>.
     *
     *
     * @param  string $text   data containing the text to read
     * @param  string $custom if provided, this is used for the link label
     *
     * @return string         returns the data with links created around urls
     */
    public static function autoLink($text, $custom = null)
    {
        $regex   = '@(http)?(s)?(://)?(([-\w]+\.)+([^\s]+)+[^,.\s])@';

        if ($custom === null) {
            $replace = '<a href="http$2://$4">$1$2$3$4</a>';
        } else {
            $replace = '<a href="http$2://$4">'.$custom.'</a>';
        }

        return preg_replace($regex, $replace, $text);
    }

    /**
     * This function converts a url segment to a safe one, for example:
     * `test name @123` will be converted to `test-name--123`
     * Basicly it works by replacing every character that isn't an letter or an number to an dash sign
     * It will also return all letters in lowercase.
     *
     * @param $slug - The url slug to convert
     *
     * @return mixed|string
     */
    public static function generateSafeSlug($slug)
    {
        setlocale(LC_ALL, "en_US.utf8");

        $slug = preg_replace('/[`^~\'"]/', null, iconv('UTF-8', 'ASCII//TRANSLIT', $slug));

        $slug = htmlentities($slug, ENT_QUOTES, 'UTF-8');

        $pattern = '~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i';
        $slug = preg_replace($pattern, '$1', $slug);

        $slug = html_entity_decode($slug, ENT_QUOTES, 'UTF-8');

        $pattern = '~[^0-9a-z]+~i';
        $slug = preg_replace($pattern, '-', $slug);

        return strtolower(trim($slug, '-'));
    }

}
