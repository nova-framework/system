<?php

namespace Nova\Assets;

use Nova\Support\Arr;
use Nova\View\Factory as ViewFactory;

use BadMethodCallException;
use InvalidArgumentException;


class AssetManager
{
    /**
     * The View Factory instance.
     *
     * @var \Nova\View\Factory
     */
     protected $views;

    /**
     * The Assets Types
     *
     * @var array
     */
    protected $types = array('css', 'js');

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
     *  The standard Asset Templates
     *
     * @var array
     */
    protected $templates = array(
        'standard' => array(
            'css' => '<link href="%s" rel="stylesheet" type="text/css">',
            'js'  => '<script src="%s" type="text/javascript"></script>',
        ),
        'inline' => array(
            'css' => '<style>%s</style>',
            'js'  => '<script type="text/javascript">%s</script>',
        ),
    );


    /**
     * Create a new Assets Manager instance.
     *
     * @return void
     */
    public function __construct(ViewFactory $views)
    {
        $this->views = $views;
    }

    /**
     * Register new Assets.
     *
     * @param  string|array $assets
     * @param  string $type
     * @param  string $position
     * @param  int $order
     * @param  string $mode
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function register($assets, $type, $position, $order = 0, $mode = 'default')
    {
        if (! in_array($type, $this->types)) {
            throw new InvalidArgumentException("Invalid assets type [${type}]");
        } else if (! in_array($mode, array('default', 'inline', 'view'))) {
            throw new InvalidArgumentException("Invalid assets mode [${mode}]");
        }

        // The assets type and mode are valid.
        else if (empty($assets = $this->parseAssets($assets))) {
            return;
        }

        // Check the assets position setup.
        else if (! Arr::has($this->positions[$type], $position)) {
            $this->positions[$type][$position] = array();
        }

        foreach ($assets as $asset) {
            $this->positions[$type][$position][] = compact('asset', 'order', 'mode');
        }
    }

    /**
     * Render the Assets for a specified position.
     *
     * @param  string $position
     * @param  string $type
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function position($position, $type)
    {
        if (! in_array($type, $this->types)) {
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

        return implode("\n", array_map(function ($item) use ($type)
        {
            $asset = Arr::get($item, 'asset');

            if (($mode = Arr::get($item, 'mode')) === 'view') {
                $mode = 'inline'; // The 'view' mode is a specialized 'inline'

                $asset = $this->views->fetch($asset);
            }

            $template = Arr::get($this->templates, "${mode}.${type}");

            return sprintf($template, $asset);

        }, $items));
    }

    /**
     * Render the CSS or JS scripts.
     *
     * @param string       $type
     * @param string|array $assets
     *
     * @return string|null
     * @throws \InvalidArgumentException
     */
    public function render($type, $assets)
    {
        if (! in_array($type, $this->types)) {
            throw new InvalidArgumentException("Invalid assets type [${type}]");
        }

        // The assets type is valid.
        else if (empty($assets = $this->parseAssets($assets))) {
            return;
        }

        $template = Arr::get($this->templates, "standard.${type}");

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
     * Returns the known Asset Types.
     *
     * @return array
     */
    protected function getTypes()
    {
        return array_keys(static::$templates);
    }
}
