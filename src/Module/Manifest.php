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
     * Initialize the manifest
     * @param Application $app [description]
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        // Path to manifest file
        $this->path = storage_path('modules.json');

        // Try to read the file
        $files = $this->app['files'];

        if ($files->exists($this->path)) {
            $this->data = @json_decode($files->get($this->path), true);
        }
    }

    /**
     * Save the manifest data
     * @return void
     */
    public function save($modules)
    {
        // Prepare the Manifest data.
        foreach ($modules as $module) {
            $key = $module->name();

            $this->data[$key] = $module->get();
        }

        // Cache it
        try
        {
            $data = $this->app['modules']->prettyJsonEncode($this->data);

            $this->app['files']->put($this->path, $data);
        } catch(\Exception $e) {
            $this->app['log']->error("[MODULES] Failed when saving manifest file: " . $e->getMessage());
        }

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
