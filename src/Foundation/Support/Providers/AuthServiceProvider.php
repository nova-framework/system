<?php

namespace Nova\Foundation\Support\Providers;

use Nova\Auth\Access\GateInterface as GateContract;

use Nova\Support\ServiceProvider;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = array();

    
    /**
     * Register the application's policies.
     *
     * @param  \Nova\Contracts\Auth\Access\Gate  $gate
     * @return void
     */
    public function registerPolicies(GateContract $gate)
    {
        foreach ($this->policies as $key => $value) {
            $gate->policy($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }
}
