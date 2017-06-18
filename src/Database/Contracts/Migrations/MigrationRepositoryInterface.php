<?php

namespace Nova\Database\Contracts\Migrations;


interface MigrationRepositoryInterface
{
	/**
	 * Get the ran migrations for a given package.
	 *
	 * @return array
	 */
	public function getRan();

	/**
	 * Get the last migration batch.
	 *
	 * @param  string  $group
	 *
	 * @return array
	 */
	public function getLast($group);

	/**
	 * Log that a migration was run.
	 *
	 * @param  string  $file
	 * @param  int	 $batch
	 * @param  string  $group
	 * @return void
	 */
	public function log($file, $batch, $group);

	/**
	 * Remove a migration from the log.
	 *
	 * @param  object  $migration
	 * @return void
	 */
	public function delete($migration);

	/**
	 * Get the next migration batch number.
	 *
	 * @param  string  $group
	 *
	 * @return int
	 */
	public function getNextBatchNumber($group);

	/**
	 * Create the migration repository data store.
	 *
	 * @return void
	 */
	public function createRepository();

	/**
	 * Determine if the migration repository exists.
	 *
	 * @return bool
	 */
	public function repositoryExists();

	/**
	 * Set the information source to gather data.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setSource($name);

}
