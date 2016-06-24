<?php

namespace Nova\Http;

use Nova\Support\MessageBag;
use Nova\Session\Store as SessionStore;
use Nova\Support\Contracts\MessageProviderInterface;

use Symfony\Component\HttpFoundation\Cookie;


class RedirectResponse extends \Symfony\Component\HttpFoundation\RedirectResponse
{
    /**
     * The request instance.
     *
     * @var \Http\Request
     */
    protected $request;

    /**
     * The session store implementation.
     *
     * @var \Session\Store
     */
    protected $session;

    /**
     * Set a header on the Response.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  bool  $replace
     * @return \Nova\Http\RedirectResponse
     */
    public function header($key, $value, $replace = true)
    {
        $this->headers->set($key, $value, $replace);

        return $this;
    }

    /**
     * Flash a piece of data to the session.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return \Nova\Http\RedirectResponse
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) $this->with($k, $v);
        } else {
            $this->session->flash($key, $value);
        }

        return $this;
    }

    /**
     * Add a cookie to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie  $cookie
     * @return \Nova\Http\RedirectResponse
     */
    public function withCookie(Cookie $cookie)
    {
        $this->headers->setCookie($cookie);

        return $this;
    }

    /**
     * Flash an array of input to the session.
     *
     * @param  array  $input
     * @return \Nova\Http\RedirectResponse
     */
    public function withInput(array $input = null)
    {
        $input = $input ?: $this->request->input();

        $this->session->flashInput($input);

        return $this;
    }

    /**
     * Flash an array of input to the session.
     *
     * @param  dynamic  string
     * @return \Nova\Http\RedirectResponse
     */
    public function onlyInput()
    {
        return $this->withInput($this->request->only(func_get_args()));
    }

    /**
     * Flash an array of input to the session.
     *
     * @param  dynamic  string
     * @return \Nova\Http\RedirectResponse
     */
    public function exceptInput()
    {
        return $this->withInput($this->request->except(func_get_args()));
    }

    /**
     * Flash a container of errors to the session.
     *
     * @param  \Support\Contracts\MessageProviderInterface|array  $provider
     * @return \Nova\Http\RedirectResponse
     */
    public function withErrors($provider)
    {
        if ($provider instanceof MessageProviderInterface) {
            $this->with('errors', $provider->getMessageBag());
        } else {
            $this->with('errors', new MessageBag((array) $provider));
        }

        return $this;
    }

    /**
     * Flash a array containing a message to the session.
     *
     * @param string $message
     * @param string $type
     *
     * @return \Nova\Http\RedirectResponse
     */
    public function withStatus($message, $type = 'success')
    {
        $status = array('type' => $type, 'text' => $message);

        $this->session->push('status', $status);

        return $this;
    }

    /**
     * Get the Request instance.
     *
     * @return  \Nova\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the Request instance.
     *
     * @param  \Nova\Http\Request  $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the Session Store implementation.
     *
     * @return \Nova\Session\Store
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the Session Store implementation.
     *
     * @param \Session\Store  $store
     * @return void
     */
    public function setSession(SessionStore $session)
    {
        $this->session = $session;
    }

    /**
     * Sets the redirect target of this response.
     *
     * @param string $url The URL to redirect to
     *
     * @return RedirectResponse The current response.
     *
     * @throws \InvalidArgumentException
     */
    public function setTargetUrl($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $this->targetUrl = $url;

        $this->setContent('');

        $this->headers->set('Location', $url);

        return $this;
    }

    /**
     * Dynamically bind flash data in the Session.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'with')) {
            return $this->with(lcfirst(substr($method, 4)), $parameters[0]);
        }

        throw new \BadMethodCallException("Method [$method] does not exist on Redirect.");
    }
}
