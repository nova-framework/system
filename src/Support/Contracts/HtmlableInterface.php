<?php

namespace Nova\Support\Contracts;


interface HtmlableInterface
{
    /**
     * Get content as a string of HTML.
     *
     * @return string
     */
    public function toHtml();
}
