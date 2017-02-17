<?php

namespace Nova\Foundation\Auth;

use Nova\Http\Request;
use Nova\Support\Facades\Auth;
use Nova\Support\Facades\Redirect;
use Nova\Support\Facades\View;


trait RegistersUsersTrait
{
    use RedirectsUsersTrait;


    /**
     * Show the application registration form.
     *
     * @return \Nova\Http\Response
     */
    public function getRegister()
    {
        return View::make('Auth/Register')
            ->shares('title', __d('nova', 'User Registration'));
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Nova\Http\Request  $request
     * @return \Nova\Http\Response
     */
    public function postRegister(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            $this->throwValidationException($request, $validator);
        }

        Auth::login($this->create($request->all()));

        return Redirect::to($this->redirectPath());
    }
}
