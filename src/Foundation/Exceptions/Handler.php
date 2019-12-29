<?php

namespace Nova\Foundation\Exceptions;

use Nova\Auth\Access\AuthorizationException;
use Nova\Auth\AuthenticationException;
use Nova\Container\Container;
use Nova\Database\ORM\ModelNotFoundException;
use Nova\Http\Exception\HttpResponseException;
use Nova\Http\Request;
use Nova\Http\Response as HttpResponse;
use Nova\Foundation\Exceptions\HandlerInterface;
use Nova\Support\Facades\Config;
use Nova\Support\Facades\Redirect;
use Nova\Support\Facades\Response;
use Nova\Validation\ValidationException;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use Psr\Log\LoggerInterface;

use Exception;
use Throwable;


class Handler implements HandlerInterface
{
    /**
     * The container implementation.
     *
     * @var \Nova\Container\Container
     */
    protected $container;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = array();


    /**
     * Create a new exception handler instance.
     *
     * @param  \Psr\Log\LoggerInterface  $log
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        if (method_exists($e, 'report')) {
            return $e->report();
        }

        try {
            $logger = $this->container->make(LoggerInterface::class);
        }
        catch (Exception $ex) {
            throw $e; // Throw the original exception
        }

        $logger->error($e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Exception  $e
     * @return bool
     */
    public function shouldReport(Exception $e)
    {
        return ! $this->shouldntReport($e);
    }

    /**
     * Determine if the exception should not be reported.
     *
     * @param  \Exception  $e
     * @return bool
     */
    public function shouldntReport(Exception $e)
    {
        $dontReport = array_merge($this->dontReport, array(
            HttpResponseException::class
        ));

        foreach ($dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare exception for rendering.
     *
     * @param  \Exception  $e
     * @return \Exception
     */
    protected function prepareException(Exception $e)
    {
        if ($e instanceof ModelNotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        } else if ($e instanceof AuthorizationException) {
            $e = new HttpException(403, $e->getMessage());
        }

        return $e;
    }

    /**
     * Render an exception into a response.
     *
     * @param  \Nova\Http\Request  $request
     * @param  \Exception  $e
     * @return \Nova\Http\Response
     */
    public function render(Request $request, Exception $e)
    {
        $e = $this->prepareException($e);

        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } else if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        } else if ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        }

        return $this->prepareResponse($request, $e);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Nova\Validation\ValidationException  $e
     * @param  \Nova\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, Request $request)
    {
        if ($e->response) {
            return $e->response;
        }

        $errors = $e->validator->errors()->getMessages();

        if ($request->ajax() || $request->wantsJson()) {
            return Response::json($errors, 422);
        }

        return Redirect::back()->withInput($request->input())->withErrors($errors);
    }

    /**
     * Prepare response containing exception render.
     *
     * @param  \Nova\Http\Request  $request
     * @param  \Exception $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function prepareResponse(Request $request, Exception $e)
    {
        if ($this->isHttpException($e)) {
            return $this->createResponse($this->renderHttpException($e, $request), $e);
        } else {
            return $this->createResponse($this->convertExceptionToResponse($e, $request), $e);
        }
    }

    /**
     * Map exception into a Nova response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Exception  $e
     * @return \Nova\Http\Response
     */
    protected function createResponse($response, Exception $e)
    {
        $response = new HttpResponse($response->getContent(), $response->getStatusCode(), $response->headers->all());

        return $response->withException($e);
    }

    /**
     * Render the given HttpException.
     *
     * @param  \Symfony\Component\HttpKernel\Exception\HttpException  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderHttpException(HttpException $e, Request $request)
    {
        return $this->convertExceptionToResponse($e, $request);
    }

    /**
     * Convert the given exception into a Response instance.
     *
     * @param  \Exception  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertExceptionToResponse(Exception $e, Request $request)
    {
        $debug = Config::get('app.debug');

        //
        $e = FlattenException::create($e);

        $handler = new SymfonyExceptionHandler($debug);

        return SymfonyResponse::create($handler->getHtml($e), $e->getStatusCode(), $e->getHeaders());
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @param  \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        with(new ConsoleApplication)->renderException($e, $output);
    }

    /**
     * Determine if the given exception is an HTTP exception.
     *
     * @param  \Exception  $e
     * @return bool
     */
    protected function isHttpException(Exception $e)
    {
        return $e instanceof HttpException;
    }
}
