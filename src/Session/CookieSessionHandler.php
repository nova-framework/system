<?php

namespace Nova\Session;

use Nova\Cookie\CookieJar;

use Symfony\Component\HttpFoundation\Request;


class CookieSessionHandler implements \SessionHandlerInterface
{
    /**
     * The cookie jar instance.
     *
     * @var \Nova\Cookie\CookieJar
     */
    protected $cookies;

    /**
     * The request instance.
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * Create a new cookie driven handler instance.
     *
     * @param  \Nova\Cookie\CookieJar  $cookie
     * @param  int  $minutes
     * @return void
     */
    public function __construct(CookieJar $cookies, $minutes)
    {
        $this->cookies = $cookies;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritDoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read($sessionId)
    {
        $cookie = $this->getSessionCookie($sessionId);

        return $this->request->cookies->get($cookie) ?: '';
    }

    /**
     * {@inheritDoc}
     */
    public function write($sessionId, $data)
    {
        $cookie = $this->getSessionCookie($sessionId);

        $this->cookies->queue($cookie, $data, $this->minutes);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy($sessionId)
    {
        $cookie = $this->getSessionCookie($sessionId);

        $this->cookies->queue($this->cookies->forget($cookie));
    }

    /**
     * {@inheritDoc}
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Set the request instance.
     *
     * @param  string  $sessionId
     * @return string
     */
    protected function getSessionCookie($sessionId)
    {
        return PREFIX .'session_' .$sessionId;
    }

    /**
     * Set the request instance.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

}
