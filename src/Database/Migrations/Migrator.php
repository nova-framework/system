<?php

namespace Nova\Database\Migrations;

use Nova\Filesystem\Filesystem;
use Nova\Database\ConnectionResolverInterface as Resolver;

class Migrator
{
    /**
     * The migration repository implementation.
     *
     * @var \Nova\Database\Migrations\MigrationRepositoryInterface
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Nova\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The connection resolver instance.
     *
     * @var \Nova\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * The name of the default connection.
     *
     * @var string
     */
    protected $connection;

    /**
     * The notes for the current operation.
     *
     * @var array
     */
    protected $notes = array();

    /**
     * Create a new migrator instance.
     *
     * @param  \Nova\Database\Migrations\MigrationRepositoryInterface  $repository
     * @param  \Nova\Database\ConnectionResolverInterface  $resolver
     * @param  \Nova\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(MigrationRepositoryInterface $repository,
                                Resolver $resolver,
                                Filesystem $files)
    {
        $this->files = $files;
        $this->resolver = $resolver;
        $this->repository = $repository;
    }

    /**
     * Run the outstanding migrations at a given path.
     *
     * @param  string  $path
     * @param  bool    $pretend
     * @return void
     */
    public function run($path, $pretend = false)
    {
        $this->notes = array();

        $this->requireFiles($path, $files = $this->getMigrationFiles($path));

        //
        $ran = $this->repository->getRan();

        $migrations = array_diff($files, $ran);

        $this->runMigrationList($migrations, $pretend);
    }

    /**
     * Run an array of migrations.
     *
     * @param  array  $migrations
     * @param  bool   $pretend
     * @return void
     */
    public function runMigrationList($migrations, $pretend = false)
    {
        if (count($migrations) == 0) {
            $this->note('<info>Nothing to migrate.</info>');

            return;
        }

        $batch = $this->repository->getNextBatchNumber();

        foreach ($migrations as $file) {
            $this->runUp($file, $batch, $pretend);
        }
    }

    /**
     * Run "up" a migration instance.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  bool    $pretend
     * @return void
     */
    protected function runUp($file, $batch, $pretend)
    {
        $migration = $this->resolve($file);

        if ($pretend) {
            return $this->pretendToRun($migration, 'up');
        }

        $migration->up();

        //
        $this->repository->log($file, $batch);

        $this->note("<info>Migrated:</info> $file");
    }

    /**
     * Rollback the last migration operation.
     *
     * @param  bool  $pretend
     * @return int
     */
    public function rollback($pretend = false)
    {
        $this->notes = array();

        $migrations = $this->repository->getLast();

        if (count($migrations) == 0) {
            $this->note('<info>Nothing to rollback.</info>');

            return count($migrations);
        }

        foreach ($migrations as $migration) {
            $this->runDown((object) $migration, $pretend);
        }

        return count($migrations);
    }

    /**
     * Run "down" a migration instance.
     *
     * @param  object  $migration
     * @param  bool    $pretend
     * @return void
     */
    protected function runDown($migration, $pretend)
    {
        $file = $migration->migration;

        $instance = $this->resolve($file);

        if ($pretend) {
            return $this->pretendToRun($instance, 'down');
        }

        $instance->down();

        $this->repository->delete($migration);

        $this->note("<info>Rolled back:</info> $file");
    }

    /**
     * Get all of the migration files in a given path.
     *
     * @param  string  $path
     * @return array
     */
    public function getMigrationFiles($path)
    {
        $files = $this->files->glob($path .DS .'*_*.php');

        if ($files === false) return array();

        $files = array_map(function($file)
        {
            return str_replace('.php', '', basename($file));

        }, $files);

        //
        sort($files);

        return $files;
    }

    /**
     * Require in all the migration files in a given path.
     *
     * @param  array  $files
     * @return void
     */
    public function requireFiles($path, array $files)
    {
        foreach ($files as $file) {
            $this->files->requireOnce($path .DS .$file.'.php');
        }
    }

    /**
     * Pretend to run the migrations.
     *
     * @param  object  $migration
     * @return void
     */
    protected function pretendToRun($migration, $method)
    {
        foreach ($this->getQueries($migration, $method) as $query) {
            $name = get_class($migration);

            $this->note("<info>{$name}:</info> {$query['query']}");
        }
    }

    /**
     * Get all of the queries that would be run for a migration.
     *
     * @param  object  $migration
     * @param  string  $method
     * @return array
     */
    protected function getQueries($migration, $method)
    {
        $connection = $migration->getConnection();

        $db = $this->resolveConnection($connection);

        return $db->pretend(function() use ($migration, $method)
        {
            $migration->$method();
        });
    }

    /**
     * Resolve a migration instance from a file.
     *
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        $file = implode('_', array_slice(explode('_', $file), 4));

        $className = Inflector::tableize($file);

        return new $className();
    }

    /**
     * Raise a note event for the migrator.
     *
     * @param  string  $message
     * @return void
     */
    protected function note($message)
    {
        $this->notes[] = $message;
    }

    /**
     * Get the notes for the last operation.
     *
     * @return array
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Resolve the database connection instance.
     *
     * @return \Nova\Database\Connection
     */
    public function resolveConnection()
    {
        return $this->resolver->connection($this->connection);
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setConnection($name)
    {
        if ( ! is_null($name)) {
            $this->resolver->setDefaultConnection($name);
        }

        $this->repository->setSource($name);

        $this->connection = $name;
    }

    /**
     * Get the migration repository instance.
     *
     * @return \Nova\Database\Migrations\MigrationRepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists()
    {
        return $this->repository->repositoryExists();
    }

    /**
     * Get the file system instance.
     *
     * @return \Nova\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }

}
