<?php

namespace Nova\View;

use Nova\View\Compilers\MarkdownCompiler;
use Nova\View\Compilers\TemplateCompiler;
use Nova\View\Engines\EngineResolver;
use Nova\View\Engines\CompilerEngine;
use Nova\View\Engines\FileEngine;
use Nova\View\Engines\PhpEngine;
use Nova\View\Factory;
use Nova\View\FileViewFinder;
use Nova\View\Section;
use Nova\Support\ServiceProvider;


class ViewServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the Provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the Service Provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEngineResolver();

        $this->registerViewFinder();

        $this->registerFactory();

        $this->registerSection();
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $this->app->singleton('view.engine.resolver', function($app)
        {
            $resolver = new EngineResolver();

            foreach (array('php', 'template', 'markdown', 'file') as $engine) {
                $method = 'register' .ucfirst($engine) .'Engine';

                call_user_func(array($this, $method), $resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param  \Nova\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function()
        {
            return new PhpEngine();
        });
    }

    /**
     * Register the Template engine implementation.
     *
     * @param  \Nova\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerTemplateEngine($resolver)
    {
        $app = $this->app;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Template compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $app->singleton('template.compiler', function($app)
        {
            $cachePath = $app['config']['view.compiled'];

            return new TemplateCompiler($app['files'], $cachePath);
        });

        $resolver->register('template', function() use ($app)
        {
            return new CompilerEngine($app['template.compiler'], $app['files']);
        });
    }

    /**
     * Register the Markdown engine implementation.
     *
     * @param  \Nova\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerMarkdownEngine($resolver)
    {
        $app = $this->app;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Markdown compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $app->singleton('markdown.compiler', function($app)
        {
            $cachePath = $app['config']['view.compiled'];

            return new MarkdownCompiler($app['files'], $cachePath);
        });

        $resolver->register('markdown', function() use ($app)
        {
            return new CompilerEngine($app['markdown.compiler'], $app['files']);
        });
    }

    /**
     * Register the File engine implementation.
     *
     * @param  \Nova\View\Engines\EngineResolver  $resolver
     * @return void
     */
    public function registerFileEngine($resolver)
    {
        $resolver->register('file', function()
        {
            return new FileEngine();
        });
    }

    /**
     * Register the View Factory.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->app->singleton('view', function($app)
        {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Template engine.
            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $factory = new Factory($resolver, $finder, $app['events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $this->app->singleton('view.finder', function($app)
        {
            $paths = $app['config']->get('view.paths', array());

            return new FileViewFinder($app['files'], $paths);
        });
    }

    /**
     * Register the View Section instance.
     *
     * @return void
     */
    public function registerSection()
    {
        $this->app->singleton('view.section', function($app)
        {
            return new Section($app['view']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'view', 'view.finder', 'view.engine.resolver',
            'template', 'template.compiler',
            'markdown', 'markdown.compiler',
            'section'
        );
    }
}
