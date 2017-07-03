<?php

namespace Nova\Foundation\Exceptions;

use Nova\Auth\Access\UnauthorizedException;
use Nova\Container\Container;
use Nova\Http\Exception\HttpResponseException;
use Nova\Http\Response as HttpResponse;
use Nova\Foundation\Contracts\ExceptionHandlerInterface;
use Nova\Support\Facades\Config;
use Nova\Support\Facades\Response;
use Nova\View\View;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

use Psr\Log\LoggerInterface;

use Exception;
use Throwable;


class Handler implements ExceptionHandlerInterface
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
	 * Determine if the exception should be reported.
	 *
	 * @param  \Exception  $e
	 * @return bool
	 */
	public function shouldntReport(Exception $e)
	{
		$dontReport = array_merge($this->dontReport, array(
			HttpResponseException::class
		));

		foreach ($this->dontReport as $type) {
			if ($e instanceof $type) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render an exception into a response.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Nova\Http\Response
	 */
	public function render($request, Exception $e)
	{
		if ($e instanceof HttpResponseException) {
			return $e->getResponse();
		} else if ($this->isUnauthorizedException($e)) {
			$e = new HttpException(403, $e->getMessage());
		}

		if ($this->isHttpException($e)) {
			return $this->createResponse($this->renderHttpException($e), $e);
		}

		return $this->createResponse($this->convertExceptionToResponse($e), $e);
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
	protected function renderHttpException(HttpException $e)
	{
		return $this->convertExceptionToResponse($e);
	}

	/**
	 * Convert the given exception into a Response instance.
	 *
	 * @param  \Exception  $e
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function convertExceptionToResponse(Exception $e)
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
	 * Determine if the given exception is an access unauthorized exception.
	 *
	 * @param  \Exception  $e
	 * @return bool
	 */
	protected function isUnauthorizedException(Exception $e)
	{
		return ($e instanceof UnauthorizedException);
	}

	/**
	 * Determine if the given exception is an HTTP exception.
	 *
	 * @param  \Exception  $e
	 * @return bool
	 */
	protected function isHttpException(Exception $e)
	{
		return ($e instanceof HttpException);
	}
}
