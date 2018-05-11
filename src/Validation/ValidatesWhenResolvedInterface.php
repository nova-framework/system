<?php

namespace Nova\Validation;


interface ValidatesWhenResolvedInterface
{
    /**
     * Validate the given class instance.
     *
     * @return void
     */
    public function validate();
}
