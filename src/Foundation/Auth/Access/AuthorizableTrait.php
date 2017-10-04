<?php

namespace Nova\Foundation\Auth\Access;

use Nova\Auth\Access\GateInterface as Gate;
use Nova\Support\Facades\App;


trait AuthorizableTrait
{
    /**
     * Determine if the entity has a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = array())
    {
        $gate = App::make(Gate::class)->forUser($this);

        return $gate->check($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = array())
    {
        return $this->cant($ability, $arguments);
    }
}
