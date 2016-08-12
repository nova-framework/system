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

    /**
     * Return true if the current Renderable instance is a Layout.
     *
     * @return bool
     */
    public function isLayout();
}
