<?php

namespace Nova\Assets;

use Nova\Support\Arr;
use Nova\View\Factory as ViewFactory;

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
     *  The standard Asset Templates
     *
     * @var array
     */
    protected static $templates = array(
        'css' => '<link href="%s" rel="stylesheet" type="text/css">',
        'js'  => '<script src="%s" type="text/javascript"></script>',
    );

    /**
     *  The inline Asset Templates
     *
     * @var array
     */
    protected static $inlineTemplates = array(
        'css' => '<style>%s</style>',
        'js'  => '<script type="text/javascript">%s</script>',
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
     * @param  string $position
     * @param  string $type
     * @param  string|array $assets
     * @param  int $order
     * @param  string $mode
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function register($position, $type, $assets, $order = 0, $mode = 'default')
    {
        if (! in_array($type, $this->getTypes())) {
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
     * Render the Assets for implicit or a specified position.
     *
     * @param  string $position
     * @param  string $type
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function position($position, $type)
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

        return implode("\n", array_map(function ($item) use ($type)
        {
            $mode = Arr::get($item, 'mode');

            if ($mode === 'default') {
                $template = Arr::get(static::$templates, $type);
            } else {
                $template = Arr::get(static::$inlineTemplates, $type);
            }

            $asset = Arr::get($item, 'asset');

            if ($mode === 'view') {
                return sprintf($template, $this->views->fetch($asset));
            }

            return sprintf($template, $asset);

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
    public function render($type, $assets)
    {
        if (! in_array($type, $this->getTypes())) {
            throw new InvalidArgumentException("Invalid assets type [${type}]");
        }

        // The assets type is valid.
        else if (empty($assets = $this->parseAssets($assets))) {
            return;
        }

        $template = Arr::get(static::$templates, $type);

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
