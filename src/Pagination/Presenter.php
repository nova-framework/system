<?php
/**
 * Presenter - Implements the Pagination Presenter.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Pagination;

use Nova\Pagination\Paginator;


class Presenter
{
    /**
     * The Paginator instance.
     *
     * @var \Nova\Pagination\Paginator
     */
    protected $paginator;

    /**
     * Get the current page for the request.
     *
     * @var int
     */
    protected $currentPage;

    /**
     * Get the last available page number.
     *
     * @return int
     */
    protected $lastPage;


    /**
     * Create a new Presenter instance.
     *
     * @param  \Nova\Pagination\Paginator  $paginator
     * @return void
     */
    public function __construct(Paginator $paginator)
    {
        $this->paginator = $paginator;

        $this->currentPage = $paginator->getCurrentPage();

        $this->lastPage = $paginator->getLastPage();
    }

    /**
     * Get HTML wrapper for a page link.
     *
     * @param  string  $url
     * @param  int  $page
     * @param  string  $rel
     * @return string
     */
    public function getPageLinkWrapper($url, $page, $rel = null)
    {
        $rel = is_null($rel) ? '' : ' class="'.$rel.'"';

        return '<li><a href="'.$url.'"'.$rel.'>'.$page.'</a></li>';
    }

    /**
     * Get HTML wrapper for disabled text.
     *
     * @param  string  $text
     * @return string
     */
    public function getDisabledTextWrapper($text)
    {
        return '<li class="disabled"><span>'.$text.'</span></li>';
    }

    /**
     * Get HTML wrapper for active text.
     *
     * @param  string  $text
     * @return string
     */
    public function getActivePageWrapper($text)
    {
        return '<li class="active"><span>'.$text.'</span></li>';
    }

    /**
     * Get HTML wrapper for the entire paginator.
     *
     * @param  string  $content
     * @return string
     */
    public function getPaginationWrapper($content)
    {
        return '<ul class="pagination">' .$content .'</ul>';
    }

    /**
     * Render the Pagination contents.
     *
     * @return string
     */
    public function render()
    {
        if ($this->paginator->isQuickPaginating()) {
            $previous = __d('nova', '&laquo; Previous');

            $next = __d('nova', 'Next &raquo;');

            $content = $this->getPrevious($previous) .$this->getNext($next);

            return $this->getPaginationWrapper($content);
        }

        // The hard-coded thirteen represents the minimum number of pages we need to
        // be able to create a sliding page window. If we have less than that, we
        // will just render a simple range of page links insteadof the sliding.
        if ($this->lastPage < 13) {
            $content = $this->getPageRange(1, $this->lastPage);
        } else {
            $content = $this->getPageSlider();
        }

        $content = $this->getPrevious() .$content .$this->getNext();

        return $this->getPaginationWrapper($content);
    }

    /**
     * Build a range of numeric pagination links.
     *
     * For the current page, an HTML span element will be generated instead of a link.
     *
     * @param  int     $start
     * @param  int     $end
     * @return string
     */
    protected function getPageRange($start, $end)
    {
        $pages = array();

        for ($page = $start; $page <= $end; $page++) {
            if ($this->currentPage == $page) {
                $pages[] = $this->getActivePageWrapper($page);
            } else {
                $pages[] = $this->getLink($page);
            }
        }

        return implode(' ', $pages);
    }

    /**
     * Create a pagination slider link window.
     *
     * @return string
     */
    protected function getPageSlider()
    {
        $window = 6;

        // If the current page is very close to the beginning of the page range, we will
        // just render the beginning of the page range, followed by the last 2 of the
        // links in this list, since we will not have room to create a full slider.
        if ($this->currentPage <= $window) {
            $ending = $this->getFinish();

            return $this->getPageRange(1, $window + 2) .$ending;
        }

        // If the current page is close to the ending of the page range we will just get
        // this first couple pages, followed by a larger window of these ending pages
        // since we're too close to the end of the list to create a full on slider.
        else if ($this->currentPage >= $this->lastPage - $window) {
            $start = $this->lastPage - 8;

            $content = $this->getPageRange($start, $this->lastPage);

            return $this->getStart() .$content;
        }

        // If we have enough room on both sides of the current page to build a slider we
        // will surround it with both the beginning and ending caps, with this window
        // of pages in the middle providing a Google style sliding paginator setup.
        else {
            $content = $this->getAdjacentRange();

            return $this->getStart() .$content .$this->getFinish();
        }
    }

    /**
     * Get the page range for the current page window.
     *
     * @return string
     */
    public function getAdjacentRange()
    {
        return $this->getPageRange($this->currentPage - 3, $this->currentPage + 3);
    }

    /**
     * Build the first two page links for a sliding page range.
     *
     * @return string
     */
    protected function getStart()
    {
        return $this->getPageRange(1, 2) .$this->getDots();
    }

    /**
     * Build the last two page links for a sliding page range.
     *
     * @return string
     */
    protected function getFinish()
    {
        $content = $this->getPageRange($this->lastPage - 1, $this->lastPage);

        return $this->getDots() .$content;
    }

    /**
     * Generate the "previous" HTML link.
     *
     * <code>
     *      // Create the "previous" pagination element
     *      echo $presenter->getPrevious();
     *
     *      // Create the "previous" pagination element with custom text
     *      echo $presenter->getPrevious('Go Back');
     * </code>
     *
     * @param  string  $text
     * @return string
     */
    public function getPrevious($text = '&laquo;')
    {
        if ($this->currentPage <= 1) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->paginator->getUrl($this->currentPage - 1);

        return $this->getPageLinkWrapper($url, $text, 'prev');
    }

    /**
     * Generate the "next" HTML link.
     *
     * <code>
     *      // Create the "next" pagination element
     *      echo $presenter->getNext();
     *
     *      // Create the "next" pagination element with custom text
     *      echo $presenter->getNext('Skip Forwards');
     * </code>
     *
     * @param  string  $text
     * @return string
     */
    public function getNext($text = '&raquo;')
    {
        if ($this->currentPage >= $this->lastPage) {
            return $this->getDisabledTextWrapper($text);
        }

        $url = $this->paginator->getUrl($this->currentPage + 1);

        return $this->getPageLinkWrapper($url, $text, 'next');
    }

    /**
     * Get a pagination "dot" element.
     *
     * @return string
     */
    public function getDots()
    {
        return $this->getDisabledTextWrapper('...');
    }

    /**
     * Create a HTML page link.
     *
     * @param  int     $page
     * @param  string  $text
     * @param  string  $class
     * @return string
     */
    protected function getLink($page)
    {
        $url = $this->paginator->getUrl($page);

        return $this->getPageLinkWrapper($url, $page);
    }

    /**
     * Set the value of the current page.
     *
     * @param  int   $page
     * @return void
     */
    public function setCurrentPage($page)
    {
        $this->currentPage = $page;
    }

    /**
     * Set the value of the last page.
     *
     * @param  int   $page
     * @return void
     */
    public function setLastPage($page)
    {
        $this->lastPage = $page;
    }
}
