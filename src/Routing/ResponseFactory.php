<?php

namespace Nova\Routing;

use Nova\Http\Response;
use Nova\Http\JsonResponse;
use Nova\Support\Traits\MacroableTrait;
use Nova\Support\Str;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


class ResponseFactory
{
	use MacroableTrait;


	/**
	 * Return a new response from the application.
	 *
	 * @param  string  $content
	 * @param  int  $status
	 * @param  array  $headers
	 * @return \Nova\Http\Response
	 */
	public function make($content = '', $status = 200, array $headers = array())
	{
		return new Response($content, $status, $headers);
	}

	/**
	 * Return a new JSON response from the application.
	 *
	 * @param  mixed  $data
	 * @param  int  $status
	 * @param  array  $headers
	 * @param  int  $options
	 * @return \Nova\Http\JsonResponse
	 */
	public function json($data = array(), $status = 200, array $headers = array(), $options = 0)
	{
		return new JsonResponse($data, $status, $headers, $options);
	}

	/**
	 * Return a new JSONP response from the application.
	 *
	 * @param  string  $callback
	 * @param  mixed  $data
	 * @param  int  $status
	 * @param  array  $headers
	 * @param  int  $options
	 * @return \Nova\Http\JsonResponse
	 */
	public function jsonp($callback, $data = array(), $status = 200, array $headers = array(), $options = 0)
	{
		return $this->json($data, $status, $headers, $options)->setCallback($callback);
	}

	/**
	 * Return a new streamed response from the application.
	 *
	 * @param  \Closure  $callback
	 * @param  int  $status
	 * @param  array  $headers
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function stream($callback, $status = 200, array $headers = array())
	{
		return new StreamedResponse($callback, $status, $headers);
	}

	/**
	 * Create a new file download response.
	 *
	 * @param  \SplFileInfo|string  $file
	 * @param  string  $name
	 * @param  array  $headers
	 * @param  string|null  $disposition
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function download($file, $name = null, array $headers = array(), $disposition = 'attachment')
	{
		$response = new BinaryFileResponse($file, 200, $headers, true, $disposition);

		if (! is_null($name)) {
			return $response->setContentDisposition($disposition, $name, str_replace('%', '', Str::ascii($name)));
		}

		return $response;
	}

	/**
	 * Return the raw contents of a binary file.
	 *
	 * @param  \SplFileInfo|string  $file
	 * @param  array  $headers
	 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
	 */
	public function file($file, array $headers = array())
	{
		return new BinaryFileResponse($file, 200, $headers);
	}
}
