<?php

namespace Nova\Pipeline;

use Closure;


interface PipelineInterface
{

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  mixed  $passable
     * @param  \Closure  $destination
     * @return mixed
     */
    public function handle($passable, Closure $destination);
}