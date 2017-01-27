<?php

namespace Nova\Contracts\Support;


interface HtmlableInterface
{
    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml();
}
