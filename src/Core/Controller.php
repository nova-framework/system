<?php
/**
 * Controller - base controller
 *
 * @author David Carr - dave@novaframework.com
 * @version 3.0
 */

namespace Nova\Core;

use Nova\Core\BaseView;
use Nova\Core\Template;
use Nova\Core\View;
use Nova\Helpers\Hooks;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use App;
use Event;
use Response;


/**
 * Core controller, all other controllers extend this base controller.
 */
abstract class Controller
{
    /**
     * The requested Method by Router.
     *
     * @var string|null
     */
    private $method = null;

    /**
     * The parameters given by Router.
     *
     * @var array
     */
    private $params = array();

    /**
     * The Module name.
     *
     * @var string|null
     */
    private $module = null;

    /**
     * The Default View.
     *
     * @var string
     */
    private $defaultView;

    /**
     * The currently used Template.
     *
     * @var string
     */
    protected $template = null;

    /**
     * The currently used Layout.
     *
     * @var string
     */
    protected $layout = 'default';

    /**
     * On the initial run, create an instance of the config class and the view class.
     */
    public function __construct()
    {
        // Adjust to the default Template, if it is not defined.
        $this->template = $this->template ?: TEMPLATE;
    }

    /**
     * Execute the Controller Method
     * @return bool
     *
     * @throw \Exception
     */
    public function execute($method, $params = array())
    {
        // Initialise the Controller's variables.
        $this->method = $method;
        $this->params = $params;

        // Setup the Controller's properties.
        $className = get_class($this);

        // Prepare the View Path using the Controller's full Name including its namespace.
        $classPath = str_replace('\\', '/', ltrim($className, '\\'));

        // First, check on the App path.
        if (preg_match('#^App/Controllers/(.*)$#i', $classPath, $matches)) {
            $this->defaultView = $matches[1] .DS .ucfirst($method);
            // Secondly, check on the Modules path.
        } else if (preg_match('#^App/Modules/(.+)/Controllers/(.*)$#i', $classPath, $matches)) {
            $this->module = $matches[1];

            // The View is in Module sub-directories.
            $this->defaultView = $matches[2] .DS .ucfirst($method);
        } else {
            throw new \Exception('Failed to calculate the view and module, for the Class: ' .$className);
        }

        // Before the Action execution stage.
        $result = $this->before();

        // Process the stage result.
        if ($result instanceof SymfonyResponse) {
            return $result;
        }

        // Notify the interested Listeners about the iminent Controller's execution.
        Event::fire('framework.controller.executing', array($this, $method, $params));

        // Execute the requested Method with the given arguments.
        $result = call_user_func_array(array($this, $method), $params);

        // The Method returned a Response instance; send it and stop the processing.
        if ($result instanceof SymfonyResponse) {
            return $result;
        }

        // After the Action execution stage.
        $retval = $this->after($result);

        if($retval !== false) {
            // Create the Response and send it.
            return $this->createResponse($result);
        }

        return Response::make('');
    }

    /**
     * Create from the given result a Response instance and send it.
     *
     * @param mixed  $result
     * @return bool
     */
    protected function createResponse($result)
    {
        if (! $result instanceof BaseView) {
            // Create a Response instance and return it.
            return Response::make($result);
        }

        if ((! $result instanceof Template) && ($this->layout !== false)) {
            // A View instance, having a Layout specified; create a Template instance.
            $result = Template::make($this->layout, $this->template)
                ->with('content', $result->fetch());
        }

        // Create a Response instance and return it.
        return Response::make($result);
    }

    /**
     * Method automatically invoked before the current Action, stopping the flight
     * when it returns false. This Method is supposed to be overriden for using it.
     */
    protected function before()
    {
        // Return true to continue the processing.
        return true;
    }

    /**
     * This method automatically invokes after the current Action, when it does not return a
     * null or boolean value. This Method is supposed to be overriden for using it.
     *
     * Note that the Action's returned value is passed to this Method as parameter.
     */
    protected function after($result)
    {
        return true;
    }

    /**
     * @param  string $title
     *
     * @return \Core\Controller
     */
    protected function title($title)
    {
        View::share('title', $title);
    }

    /**
     * Return a default View instance.
     *
     * @return \Core\View
     */
    protected function getView(array $data = array())
    {
        return View::make($this->defaultView, $data, $this->module);
    }

    /**
     * @return string
     */
    protected function getViewName()
    {
        return $this->defaultView;
    }

    /**
     * @return string|null
     */
    protected function getModule()
    {
        return $this->module;
    }

    /**
     * @return mixed
     */
    protected function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return mixed
     */
    protected function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return mixed
     */
    protected function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return $this->params;
    }

}
