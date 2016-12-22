<?php
namespace Nova\Helpers;

/**
 * Assets static helper.
 *
 * @author Virgil-Adrian Teaca
 * @author volter9
 * @author QsmaPL
 * @version 3.0
 */


class Assets
{
    /**
     * @var array Asset templates
     */
    protected static $templates = array(
        'js'  => '<script src="%s" type="text/javascript"></script>',
        'css' => '<link href="%s" rel="stylesheet" type="text/css">'
    );

    /**
     * Common templates for assets.
     *
     * @param string|array $files
     * @param string       $mode
     * @param bool         $fetch
     */
    protected static function resource($files, $mode, $fetch)
    {
        $result = '';

        // Adjust the files parameter.
        $files = is_array($files) ? $files : array($files);

        // Prepare the current template.
        $template = sprintf("%s\n", self::$templates[$mode]);

        foreach ($files as $file) {
            if (empty($file)) continue;

            // Append the processed resource string to the result.
            $result .= sprintf($template, $file);
        }

        if ($fetch) {
            // Return the resulted string, with no output.
            return $result;
        }

        // Output the resulted string (and return null).
        echo $result;
    }

    /**
     * Load js scripts.
     *
     * @param string|array $files The paths to resource files.
     * @param bool         $fetch Wheter or not will be returned the result.
     */
    public static function js($files, $fetch = false)
    {
        return static::resource($files, 'js', $fetch);
    }

    /**
     * Load css scripts.
     *
     * @param string|array $files The paths to resource files.
     * @param bool         $fetch Wheter or not will be returned the result.
     */
    public static function css($files, $fetch = false)
    {
        return static::resource($files, 'css', $fetch);
    }
}
