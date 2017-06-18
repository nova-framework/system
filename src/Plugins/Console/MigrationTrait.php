<?php

namespace Nova\Plugins\Console;

use InvalidArgumentException;


trait MigrationTrait
{
	/**
	 * Require (once) all migration files for the supplied plugin.
	 *
	 * @param string $plugin
	 */
	protected function requireMigrations($plugin)
	{
		$files = $this->container['files'];

		//
		$path = $this->getMigrationPath($plugin);

		$migrations = $files->glob($path.'*_*.php');

		foreach ($migrations as $migration) {
			$files->requireOnce($migration);
		}
	}

	/**
	 * Get migration directory path.
	 *
	 * @param string $slug
	 *
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	protected function getMigrationPath($slug)
	{
		$plugins = $this->container['plugins'];

		//
		$plugin = $plugins->where('slug', $slug);

		$path = $plugins->resolveClassPath($plugin);

		return $path .'Database' .DS .'Migrations' .DS;
	}
}
