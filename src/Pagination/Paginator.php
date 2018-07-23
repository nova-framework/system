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


class Paginator extends BasePaginator implements ArrayableInterface, ArrayAccess, Countable, IteratorAggregate, JsonSerializable, JsonableInterface
{
    /**
     * The total number of items before slicing.
     *
     * @var int
     */
    protected $total;

    /**
     * The last available page.
     *
     * @var int
     */
    protected $lastPage;


    /**
     * Create a new paginator instance.
     *
     * @param  mixed  $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($items, $total, $perPage, $currentPage = null, array $options = array())
    {
        if (! $items instanceof Collection) {
            $items = Collection::make($items);
        }

        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->total   = $total;
        $this->perPage = $perPage;

        $this->lastPage = (int) ceil($total / $perPage);

        $this->currentPage = $this->setCurrentPage($currentPage, $this->pageName);

        $this->items = $items;

        if ($this->path !== '/') {
            $this->path = rtrim($this->path, '/');
        }
    }

    /**
     * Get the current page for the request.
     *
     * @param  int  $currentPage
     * @param  string  $pageName
     * @return int
     */
    protected function setCurrentPage($currentPage, $pageName)
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage($pageName);

        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }

    /**
     * Render the paginator using the given view.
     *
     * @param  string  $view
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
     * @param  string  $view
     * @param  array  $data
     * @return string
     */
    public function render($view = null, $data = array())
    {
        if (is_null($view)) {
            $view = static::$defaultView;
        }

        $data = array_merge($data, array(
            'paginator' => $this,
            'elements'  => $this->elements(),
        ));

        return new HtmlString(
            static::viewFactory()->make($view, $data)->render()
        );
    }

    /**
     * Get the array of elements to pass to the view.
     *
     * @return array
     */
    protected function elements()
    {
        $window = $this->getUrlWindow();

        return array_filter(array(
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ));
    }

    /**
     * Get the window of URLs to be shown.
     *
     * @param  int  $onEachSide
     * @return array
     */
    public function getUrlWindow($onEachSide = 3)
    {
        if (! $this->hasPages()) {
            return array('first' => null, 'slider' => null, 'last' => null);
        }

        $window = $onEachSide * 2;

        if ($this->lastPage() < ($window + 6)) {
            return $this->getSmallSlider();
        }

        // If the current page is very close to the beginning of the page range, we will
        // just render the beginning of the page range, followed by the last 2 of the
        // links in this list, since we will not have room to create a full slider.
        if ($this->currentPage() <= $window) {
            return $this->getSliderTooCloseToBeginning($window);
        }

        // If the current page is close to the ending of the page range we will just get
        // this first couple pages, followed by a larger window of these ending pages
        // since we're too close to the end of the list to create a full on slider.
        else if ($this->currentPage() > ($this->lastPage() - $window)) {
            return $this->getSliderTooCloseToEnding($window);
        }

        // If we have enough room on both sides of the current page to build a slider we
        // will surround it with both the beginning and ending caps, with this window
        // of pages in the middle providing a Google style sliding paginator setup.
        return $this->getFullSlider($onEachSide);
    }

    /**
     * Get the slider of URLs there are not enough pages to slide.
     *
     * @return array
     */
    protected function getSmallSlider()
    {
        return array(
            'first'  => $this->getUrlRange(1, $this->lastPage()),
            'slider' => null,
            'last'   => null,
        );
    }

    /**
     * Get the slider of URLs when too close to beginning of window.
     *
     * @param  int  $window
     * @return array
     */
    protected function getSliderTooCloseToBeginning($window)
    {
        return array(
            'first'  => $this->getUrlRange(1, $window + 2),
            'slider' => null,
            'last'   => $this->getUrlRange($this->lastPage() - 1, $this->lastPage()),
        );
    }

    /**
     * Get the slider of URLs when too close to ending of window.
     *
     * @param  int  $window
     * @return array
     */
    protected function getSliderTooCloseToEnding($window)
    {
        $last = $this->getUrlRange(
            $this->lastPage() - ($window + 2),
            $this->lastPage()
        );

        return array(
            'first'  => $this->getUrlRange(1, 2),
            'slider' => null,
            'last'   => $last,
        );
    }

    /**
     * Get the slider of URLs when a full slider can be made.
     *
     * @param  int  $onEachSide
     * @return array
     */
    protected function getFullSlider($onEachSide)
    {
        $slider = $this->getUrlRange(
            $this->currentPage() - $onEachSide,
            $this->currentPage() + $onEachSide
        );

        return array(
            'first'  => $this->getUrlRange(1, 2),
            'slider' => $slider,
            'last'   => $this->getUrlRange($this->lastPage() - 1, $this->lastPage()),
        );
    }

    /**
     * Get the total number of items being paginated.
     *
     * @return int
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * Determine if there are pages to show.
     *
     * @return bool
     */
    public function hasPages()
    {
        return $this->lastPage() > 1;
    }

    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->currentPage() < $this->lastPage();
    }

    /**
     * Get the URL for the next page.
     *
     * @return string|null
     */
    public function nextPageUrl()
    {
        if ($this->lastPage() > $this->currentPage()) {
            return $this->url($this->currentPage() + 1);
        }
    }

    /**
     * Get the last page.
     *
     * @return int
     */
    public function lastPage()
    {
        return $this->lastPage;
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'total'         => $this->total(),
            'per_page'      => $this->perPage(),
            'current_page'  => $this->currentPage(),
            'last_page'     => $this->lastPage(),
            'next_page_url' => $this->nextPageUrl(),
            'prev_page_url' => $this->previousPageUrl(),
            'from'          => $this->firstItem(),
            'to'            => $this->lastItem(),
            'data'          => $this->items->toArray(),
        );
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
