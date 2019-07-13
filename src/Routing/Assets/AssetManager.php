<?php

namespace Nova\Routing\Assets;

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
     *  The Asset Templates
     *
     * @var array
     */
    protected static $templates = array(
        'default' => array(
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
        else if (! empty($items = $this->parseAssets($assets, $order, $mode))) {
            // We will merge the items for the specified type and position.

            Arr::set($this->positions, $key = "${type}.${position}", array_merge(
                Arr::get($this->positions, $key, array()), $items
            ));
        }
    }

    /**
     * Render the Assets for specified position(s)
     *
     * @param  string|array $position
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

        $positions = is_array($position) ? $position : array($position);

        //
        $result = array();

        foreach ($positions as $position) {
            $items = Arr::get($this->positions, "${type}.${position}", array());

            if (! empty($items)) {
                $result = array_merge($result, $this->renderItems($items, $type, true));
            }
        }

        return implode("\n", array_unique($result));
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
        else if (! empty($items = $this->parseAssets($assets))) {
            return implode("\n", $this->renderItems($items, $type, false));
        }
    }

    /**
     * Render the given position items to an array of assets.
     *
     * @param  array $items
     * @param string $type
     * @param bool $sorted
     *
     * @return array
     */
    protected function renderItems(array $items, $type, $sorted = true)
    {
        if ($sorted) {
            static::sortItems($items);
        }

        return array_map(function ($item) use ($type)
        {
            $asset = Arr::get($item, 'asset');

            //
            $mode = Arr::get($item, 'mode', 'default');

            if ($mode === 'inline') {
                $asset = sprintf("\n%s\n", trim($asset));
            }

            // The 'view' mode is a specialized 'inline'
            else if ($mode === 'view') {
                $mode = 'inline';

                $asset = $this->views->fetch($asset);
            }

            $template = Arr::get(static::$templates, "${mode}.${type}");

            return sprintf($template, $asset);

        }, $items);
    }

    /**
     * Sort the given items by their order.
     *
     * @param  array $items
     *
     * @return void
     */
    protected static function sortItems(array &$items)
    {
        usort($items, function ($a, $b)
        {
            if ($a['order'] === $b['order']) {
                return 0;
            }

            return ($a['order'] < $b['order']) ? -1 : 1;
        });
    }

    /**
     * Parses and returns the given assets.
     *
     * @param  string|array $assets
     * @param  int $order
     * @param  string $mode
     *
     * @return array
     */
    protected function parseAssets($assets, $order = 0, $mode = 'default')
    {
        if (is_string($assets) && ! empty($assets)) {
            $assets = array($assets);
        } else if (! is_array($assets)) {
            return array();
        }

        return array_map(function ($asset) use ($order, $mode)
        {
            return compact('asset', 'order', 'mode');

        }, array_filter($assets, function ($value)
        {
            return ! empty($value);
        }));
    }
}
