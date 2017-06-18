<?php

namespace Nova\Auth;

use Nova\Auth\Access\Gate;
use Nova\Support\ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerAuthenticator();

		$this->registerUserResolver();

		$this->registerAccessGate();

		$this->registerRequestRebindHandler();
	}

	/**
	 * Register the authenticator services.
	 *
	 * @return void
	 */
	protected function registerAuthenticator()
	{
		$this->app->singleton('auth', function ($app)
		{
			// Once the authentication service has actually been requested by the developer
			// we will set a variable in the application indicating such. This helps us
			// know that we need to set any queued cookies in the after event later.
			$app['auth.loaded'] = true;

			return new AuthManager($app);
		});

		$this->app->singleton('auth.driver', function ($app)
		{
			return $app['auth']->guard();
		});
	}

	/**
	 * Register a resolver for the authenticated user.
	 *
	 * @return void
	 */
	protected function registerUserResolver()
	{
		$this->app->bind('Nova\Auth\Contracts\UserInterface', function ($app)
		{
			$callback = $app['auth']->userResolver();

			return call_user_func($callback);
		});
	}

	/**
	 * Register the access gate service.
	 *
	 * @return void
	 */
	protected function registerAccessGate()
	{
		$this->app->singleton('Nova\Auth\Contracts\Access\GateInterface', function ($app)
		{
			return new Gate($app, function() use ($app)
			{
				$callback = $app['auth']->userResolver();

				return call_user_func($callback);
			});
		});
	}

	/**
	 * Register a resolver for the authenticated user.
	 *
	 * @return void
	 */
	protected function registerRequestRebindHandler()
	{
		$this->app->rebinding('request', function ($app, $request)
		{
			$request->setUserResolver(function ($guard = null) use ($app)
			{
				$callback = $app['auth']->userResolver();

				return call_user_func($callback, $guard);
			});
		});
	}

}
