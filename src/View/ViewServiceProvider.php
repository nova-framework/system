<?php

namespace Nova\View;

use Nova\View\Compilers\TemplateCompiler;
use Nova\View\Engines\EngineResolver;
use Nova\View\Engines\CompilerEngine;
use Nova\View\Engines\PhpEngine;
use Nova\View\Factory;
use Nova\View\FileViewFinder;
use Nova\Support\MessageBag;
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

        $this->registerSessionBinder();

        $this->registerCommands();
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $this->app->bindShared('view.engine.resolver', function($app)
        {
            $resolver = new EngineResolver();

            foreach (array('php', 'template') as $engine) {
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
        $app->bindShared('template.compiler', function($app)
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
     * Register the View Factory.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->app->bindShared('view', function($app)
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
        $this->app->bindShared('view.finder', function($app)
        {
            return new FileViewFinder($app['files']);
        });
    }

    /**
     * Register the session binder for the view environment.
     *
     * @return void
     */
    protected function registerSessionBinder()
    {
        list($app, $me) = array($this->app, $this);

        $app->booted(function() use ($app, $me)
        {
            // If the current session has an "errors" variable bound to it, we will share
            // its value with all view instances so the views can easily access errors
            // without having to bind. An empty bag is set when there aren't errors.
            if ($me->sessionHasErrors($app)) {
                $errors = $app['session.store']->get('errors');

                $app['view']->share('errors', $errors);
            } else {
                $app['view']->share('errors', new MessageBag());
            }
        });
    }

    /**
     * Register the view related console commands.
     *
     * @return void
     */
    public function registerCommands()
    {
        $this->app->bindShared('command.view.clear', function($app)
        {
            $cachePath = $app['config']['view.compiled'];

            return new Console\ClearCommand($app['files'], $cachePath);
        });

        $this->commands('command.view.clear');
    }

    /**
     * Determine if the application session has errors.
     *
     * @param  \Nova\Foundation\Application  $app
     * @return bool
     */
    public function sessionHasErrors($app)
    {
        $config = $app['config']['session'];

        if (isset($app['session.store']) && ! is_null($config['driver'])) {
            return $app['session.store']->has('errors');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('view', 'view.finder', 'view.engine.resolver', 'command.view.clear');
    }
}
