<?php

namespace Nova\Pagination;

use Nova\Support\Collection;
use Nova\Support\HtmlString;
use Nova\Support\Contracts\JsonableInterface;
use Nova\Support\Contracts\ArrayableInterface;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;


class SimplePaginator extends BasePaginator implements ArrayableInterface, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, JsonableInterface
{
    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    protected $hasMore;


    /**
     * Create a new paginator instance.
     *
     * @param  mixed  $items
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($items, $perPage, $currentPage = null, array $options = array())
    {
        if (! $items instanceof Collection) {
            $items = Collection::make($items);
        }

        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->perPage = $perPage;

        $this->currentPage = $this->setCurrentPage($currentPage);

        $this->hasMore = count($items) > ($this->perPage);

        $this->items = $items->slice(0, $this->perPage);

        if ($this->path !== '/') {
            $this->path = rtrim($this->path, '/');
        }
    }

    /**
     * Get the current page for the request.
     *
     * @param  int  $currentPage
     * @return int
     */
    protected function setCurrentPage($currentPage)
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage();

        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }

    /**
     * Get the URL for the next page.
     *
     * @return string|null
     */
    public function nextPageUrl()
    {
        if ($this->hasMorePages()) {
            return $this->url($this->currentPage() + 1);
        }
    }

    /**
     * Render the paginator using the given view.
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function links($view = null, $data = array())
    {
        return $this->render($view, $data);
    }

    /**
     * Render the paginator using the given view.
     *
     * @param  string|null  $view
     * @param  array  $data
     * @return string
     */
    public function render($view = null, $data = array())
    {
        if (is_null($view)) {
            $view = static::$defaultSimpleView;
        }

        $data = array_merge($data, array(
            'paginator' => $this,
        ));

        return new HtmlString(
            static::viewFactory()->make($view, $data)->render()
        );
    }

    /**
     * Manually indicate that the paginator does have more pages.
     *
     * @param  bool  $value
     * @return $this
     */
    public function hasMorePagesWhen($value = true)
    {
        $this->hasMore = $value;

        return $this;
    }

    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->hasMore;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'per_page'      => $this->perPage(),
            'current_page'  => $this->currentPage(),
            'next_page_url' => $this->nextPageUrl(),
            'prev_page_url' => $this->previousPageUrl(),
            'from'          => $this->firstItem(),
            'to'            => $this->lastItem(),
            'data'          => $this->items->toArray(),
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}
