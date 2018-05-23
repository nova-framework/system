<?php

namespace Nova\Assets;

use Nova\Support\Arr;

use BadMethodCallException;
use InvalidArgumentException;


class AssetManager
{
    /**
     * The Assets Positions
     *
     * @var array
     */
    protected $positions = array(
        'css' => array(),
        'js'  => array(),
    );

    /**
     *  The Asset Templates
     *
     * @var array
     */
    protected static $templates = array(
        'css' => '<link href="%s" rel="stylesheet" type="text/css">',
        'js'  => '<script src="%s" type="text/javascript"></script>',
    );


    /**
     * Register new Assets.
     *
     * @param  string $type
     * @param  string|array $assets
     * @param  string|null $position
     * @param  int $order
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function register($type, $assets, $position = 'header', $order = 0)
    {
        if (! in_array($type, $this->getTypes())) {
            throw new InvalidArgumentException("Invalid assets type [${type}]");
        }

        // The assets type is valid.
        else if (empty($assets = $this->parseAssets($assets))) {
            return;
        }

        // Check the assets position setup.
        else if (! Arr::has($this->positions[$type], $position)) {
            $this->positions[$type][$position] = array();
        }

        foreach ($assets as $asset) {
            $this->positions[$type][$position][] = compact('asset', 'order');
        }
    }

    /**
     * Render the Assets for implicit or a specified position.
     *
     * @param  string $type
     * @param  string $position
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function render($type, $position = 'header')
    {
        if (! in_array($type, $this->getTypes())) {
            throw new InvalidArgumentException("Invalid assets type [${type}]");
        }

        // The assets type is valid.
        else if (empty($items = Arr::get($this->positions[$type], $position, array()))) {
            return;
        }

        usort($items, function ($a, $b)
        {
            if ($a['order'] == $b['order']) return 0;

            return ($a['order'] > $b['order']) ? 1 : -1;
        });

        $template = $this->getTemplate($type);

        return implode("\n", array_map(function ($item) use ($template)
        {
            return sprintf($template, Arr::get($item, 'asset'));

        }, $items));
    }

    /**
     * Build the CSS or JS scripts.
     *
     * @param string       $type
     * @param string|array $files
     *
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function build($type, $assets)
    {
        if (! in_array($type, $this->getTypes())) {
            throw new InvalidArgumentException("Invalid assets type [${type}]");
        }

        // The assets type is valid.
        else if (empty($assets = $this->parseAssets($assets))) {
            return;
        }

        $template = $this->getTemplate($type);

        return implode("\n", array_map(function ($asset) use ($template)
        {
            return sprintf($template, $asset);

        }, $assets));
    }

    /**
     * Parses and returns the given assets.
     *
     * @param  string|array $assets
     *
     * @return array
     */
    protected function parseAssets($assets)
    {
        if (is_string($assets) && ! empty($assets)) {
            $assets = array($assets);
        } else if (! is_array($assets)) {
            return array();
        }

        return array_filter($assets, function ($value)
        {
            return ! empty($value);
        });
    }

    /**
     * Returns an Assets Template.
     *
     * @param  string  $type
     * @return string
     */
    protected function getTemplate($type)
    {
        return Arr::get(static::$templates, $type);
    }

    /**
     * Returns the known Asset Types.
     *
     * @return array
     */
    protected function getTypes()
    {
        return array_keys(static::$templates);
    }
}
