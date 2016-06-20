<?php
/**
 * View - load template pages
 *
 * @author David Carr - dave@novaframework.com
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Core;

use Nova\Core\BaseView;
use Nova\Core\Template;

use Response;


/**
 * View class to load views files.
 */
class View extends BaseView
{
    /**
     * Constructor
     * @param mixed $path
     * @param array $data
     *
     * @throws \UnexpectedValueException
     */
    protected function __construct($view, $path, array $data = array())
    {
        parent::__construct($view, $path, $data);
    }

    /**
     * Create a View instance
     *
     * @param string $path
     * @param array|string $data
     * @param string|null $module
     * @return View
     */
    public static function make($view, $data = array(), $module = null)
    {
        if (is_string($data)) {
            if (! empty($data) && ($module === null)) {
                // The Module name given as second parameter; adjust the information.
                $module = $data;
            }

            $data = array();
        }

        // Prepare the (relative) file path according with Module parameter presence.
        if ($module !== null) {
            $path = str_replace('/', DS, APPDIR ."Modules/$module/Views/$view.php");
        } else {
            $path = str_replace('/', DS, APPDIR ."Views/$view.php");
        }

        return new View($view, $path, $data);
    }
}
