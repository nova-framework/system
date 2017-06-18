<?php

namespace Nova\Plugins;

use Nova\Plugins\Console\PluginListCommand;
use Nova\Plugins\Console\PluginMigrateCommand;
use Nova\Plugins\Console\PluginMigrateRefreshCommand;
use Nova\Plugins\Console\PluginMigrateResetCommand;
use Nova\Plugins\Console\PluginMigrateRollbackCommand;
use Nova\Plugins\Console\PluginSeedCommand;

use Nova\Plugins\Console\PluginMakeCommand;
use Nova\Plugins\Console\ControllerMakeCommand;
use Nova\Plugins\Console\MiddlewareMakeCommand;
use Nova\Plugins\Console\MigrationMakeCommand;
use Nova\Plugins\Console\ModelMakeCommand;
use Nova\Plugins\Console\PolicyMakeCommand;
use Nova\Plugins\Console\SeederMakeCommand;

use Nova\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
{

	/**
	 * Register the application services.
	 */
	public function register()
	{
		$commands = array(
			'PluginList',
			'PluginMigrate',
			'PluginMigrateRefresh',
			'PluginMigrateReset',
			'PluginMigrateRollback',
			'PluginSeed',
			'PluginMake',
			'ControllerMake',
			'MiddlewareMake',
			'ModelMake',
			'PolicyMake',
			'MigrationMake',
			'SeederMake',
		);

		foreach ($commands as $command) {
			$this->{'register' .$command .'Command'}();
		}
	}

	/**
	 * Register the plugin:list command.
	 */
	protected function registerPluginListCommand()
	{
		$this->app->singleton('command.plugin.list', function ($app)
		{
			return new PluginListCommand($app['plugins']);
		});

		$this->commands('command.plugin.list');
	}

	/**
	 * Register the plugin:migrate command.
	 */
	protected function registerPluginMigrateCommand()
	{
		$this->app->singleton('command.plugin.migrate', function ($app) {
			return new PluginMigrateCommand($app['migrator'], $app['plugins']);
		});

		$this->commands('command.plugin.migrate');
	}

	/**
	 * Register the plugin:migrate:refresh command.
	 */
	protected function registerPluginMigrateRefreshCommand()
	{
		$this->app->singleton('command.plugin.migrate.refresh', function ($app) {
			return new PluginMigrateRefreshCommand($app['plugins']);
		});

		$this->commands('command.plugin.migrate.refresh');
	}

	/**
	 * Register the plugin:migrate:reset command.
	 */
	protected function registerPluginMigrateResetCommand()
	{
		$this->app->singleton('command.plugin.migrate.reset', function ($app) {
			return new PluginMigrateResetCommand($app['plugins'], $app['files'], $app['migrator']);
		});

		$this->commands('command.plugin.migrate.reset');
	}

	/**
	 * Register the plugin:migrate:rollback command.
	 */
	protected function registerPluginMigrateRollbackCommand()
	{
		$this->app->singleton('command.plugin.migrate.rollback', function ($app) {
			return new PluginMigrateRollbackCommand($app['migrator'], $app['plugins']);
		});

		$this->commands('command.plugin.migrate.rollback');
	}

	/**
	 * Register the plugin:seed command.
	 */
	protected function registerPluginSeedCommand()
	{
		$this->app->singleton('command.plugin.seed', function ($app) {
			return new PluginSeedCommand($app['plugins']);
		});

		$this->commands('command.plugin.seed');
	}

	/**
	 * Register the make:plugin command.
	 */
	private function registerPluginMakeCommand()
	{
		$this->app->bindShared('command.make.plugin', function ($app)
		{
			return new PluginMakeCommand($app['files'], $app['plugins']);
		});

		$this->commands('command.make.plugin');
	}

	/**
	 * Register the make:plugin:controller command.
	 */
	private function registerControllerMakeCommand()
	{
		$this->app->bindShared('command.make.plugin.controller', function ($app)
		{
			return new ControllerMakeCommand($app['files'], $app['plugins']);
		});

		$this->commands('command.make.plugin.controller');
	}

	/**
	 * Register the make:plugin:middleware command.
	 */
	private function registerMiddlewareMakeCommand()
	{
		$this->app->bindShared('command.make.plugin.middleware', function ($app)
		{
			return new MiddlewareMakeCommand($app['files'], $app['plugins']);
		});

		$this->commands('command.make.plugin.middleware');
	}

	/**
	 * Register the make:plugin:model command.
	 */
	private function registerModelMakeCommand()
	{
		$this->app->bindShared('command.make.plugin.model', function ($app)
		{
			return new ModelMakeCommand($app['files'], $app['plugins']);
		});

		$this->commands('command.make.plugin.model');
	}

	/**
	 * Register the make:plugin:policy command.
	 */
	private function registerPolicyMakeCommand()
	{
		$this->app->bindShared('command.make.plugin.policy', function ($app)
		{
			return new PolicyMakeCommand($app['files'], $app['plugins']);
		});

		$this->commands('command.make.plugin.policy');
	}

	/**
	 * Register the make:plugin:migration command.
	 */
	private function registerMigrationMakeCommand()
	{
		$this->app->bindShared('command.make.plugin.migration', function ($app)
		{
			return new MigrationMakeCommand($app['files'], $app['plugins']);
		});

		$this->commands('command.make.plugin.migration');
	}

	/**
	 * Register the make:plugin:seeder command.
	 */
	private function registerSeederMakeCommand()
	{
		$this->app->bindShared('command.make.plugin.seeder', function ($app)
		{
			return new SeederMakeCommand($app['files'], $app['plugins']);
		});

		$this->commands('command.make.plugin.seeder');
	}
}
