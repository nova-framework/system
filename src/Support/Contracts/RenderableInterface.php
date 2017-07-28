<?php

namespace Nova\Support\Contracts;


interface RenderableInterface
{
    /**
     * Show the evaluated contents of the object.
     *
     * @return string
     */
    public function render();
}
