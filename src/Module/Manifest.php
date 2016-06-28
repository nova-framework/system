<?php

namespace Nova\Module;

use Nova\Foundation\Application;


class Manifest
{
    /**
     * Path to manifest file
     * @var string
     */
    protected $path;

    /**
     * Manifest data
     * @var array
     */
    protected $data;

    /**
     * IoC
     * @var Nova\Foundation\Application
     */
    protected $app;

    /**
     * Initialize the Manifest instance.
     *
     * @param \Nova\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        // Path to manifest file
        $this->path = storage_path('modules.json');

        // Try to read the file
        if (file_exists($this->path)) {
            $this->data = json_decode(file_get_contents($this->path), true);
        }
    }

    /**
     * Save the Manifest data
     * @return void
     */
    public function save($modules)
    {
        // Prepare the Manifest data.
        foreach ($modules as $module) {
            $key = $module->name();

            $this->data[$key] = $module->get();
        }

        // Cache the Manifest data.
        file_put_contents($path, json_encode($this->data));

        return $this->data;
    }

    /**
     * Get the Manifest data as an array
     *
     * @return array
     */
    public function toArray($module = null)
    {
        if (! is_null($module)) {
            return $this->data[$module];
        }

        return $this->data;
    }

    /**
     * Delete the Manifest file
     * @return void
     */
    public function delete()
    {
        $this->data = null;

        $this->app['files']->delete($this->path);
    }

}
