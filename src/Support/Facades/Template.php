<?php 

namespace Nova\Support\Facades;

/**
 * @see \Illuminate\View\Compilers\TemplateCompiler
 */
class Template extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
	return static::$app['view']->getEngineResolver()->resolve('template')->getCompiler();
    }

}
