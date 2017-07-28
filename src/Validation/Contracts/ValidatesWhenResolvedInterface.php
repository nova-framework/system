<?php

namespace Nova\Validation\Contracts;


interface ValidatesWhenResolvedInterface
{
    /**
     * Validate the given class instance.
     *
     * @return void
     */
    public function validate();
}
