<?php

namespace Nova\Foundation\Exceptions;

use Nova\Http\Request;

use Exception;


interface HandlerInterface
{

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e);

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Nova\Http\Request  $request
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render(Request $request, Exception $e);

}
