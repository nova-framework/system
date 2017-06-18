<?php

namespace Nova\Cookie\Middleware;

use Nova\Encryption\DecryptException;
use Nova\Encryption\Encrypter;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Closure;


class EncryptCookies
{
	/**
	 * The encrypter instance.
	 *
	 * @var \Nova\Encryption\Encrypter
	 */
	protected $encrypter;

	/**
	 * The names of the cookies that should not be encrypted.
	 *
	 * @var array
	 */
	protected $except = array();


	/**
	 * Create a new CookieGuard instance.
	 *
	 * @param  \Nova\Encryption\Encrypter  $encrypter
	 * @return void
	 */
	public function __construct(Encrypter $encrypter)
	{
		$this->encrypter = $encrypter;
	}

	/**
	 * Disable encryption for the given cookie name(s).
	 *
	 * @param string|array $cookieName
	 * @return void
	 */
	public function disableFor($cookieName)
	{
		$this->except = array_merge($this->except, (array) $cookieName);
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Nova\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		return $this->encrypt($next($this->decrypt($request)));
	}

	/**
	 * Decrypt the cookies on the request.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	protected function decrypt(Request $request)
	{
		foreach ($request->cookies as $key => $c) {
			if ($this->isDisabled($key)) {
				continue;
			}

			try {
				$request->cookies->set($key, $this->decryptCookie($c));
			} catch (DecryptException $e) {
				$request->cookies->set($key, null);
			}
		}

		return $request;
	}

	/**
	 * Decrypt the given cookie and return the value.
	 *
	 * @param  string|array  $cookie
	 * @return string|array
	 */
	protected function decryptCookie($cookie)
	{
		return is_array($cookie)
			? $this->decryptArray($cookie)
			: $this->encrypter->decrypt($cookie);
	}

	/**
	 * Decrypt an array based cookie.
	 *
	 * @param  array  $cookie
	 * @return array
	 */
	protected function decryptArray(array $cookie)
	{
		$decrypted = array();

		foreach ($cookie as $key => $value) {
			if (is_string($value)) {
				$decrypted[$key] = $this->encrypter->decrypt($value);
			}
		}

		return $decrypted;
	}

	/**
	 * Encrypt the cookies on an outgoing response.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Response  $response
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	protected function encrypt(Response $response)
	{
		foreach ($response->headers->getCookies() as $cookie) {
			if ($this->isDisabled($cookie->getName())) {
				continue;
			}

			$response->headers->setCookie($this->duplicate(
				$cookie, $this->encrypter->encrypt($cookie->getValue())
			));
		}

		return $response;
	}

	/**
	 * Duplicate a cookie with a new value.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Cookie  $c
	 * @param  mixed  $value
	 * @return \Symfony\Component\HttpFoundation\Cookie
	 */
	protected function duplicate(Cookie $cookie, $value)
	{
		return new Cookie(
			$cookie->getName(),
			$value,
			$cookie->getExpiresTime(),
			$cookie->getPath(),
			$cookie->getDomain(),
			$cookie->isSecure(),
			$cookie->isHttpOnly()
		);
	}

	/**
	 * Determine whether encryption has been disabled for the given cookie.
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function isDisabled($name)
	{
		return in_array($name, $this->except);
	}
}
