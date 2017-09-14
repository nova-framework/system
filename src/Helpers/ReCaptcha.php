<?php
/**
 * ReCaptcha - Manage the Google ReCaptcha Anti-spam protection.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Helpers;

use Nova\Support\Facades\Config;
use Nova\Support\Facades\Request;
use Nova\Support\Arr;

use InvalidArgumentException;


/**
 * ReCaptcha: Google Anti-spam protection for your website.
 */
class ReCaptcha
{
    /**
     * Whether or not the verification is active.
     *
     * @var bool
     */
    protected $active = true;

    /**
     * The site key.
     *
     * @var string
     */
    protected $siteKey;

    /**
     * The secret key.
     *
     * @var string
     */
    protected $secret;

    /**
     * Constant holding the Googe API url.
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';


    /**
     * Create a new ReCaptcha instance.
     *
     * @param array|null $config
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function __construct($config = null)
    {
        if (is_null($config)) {
            $config = Config::get('reCaptcha', array());
        } else if (! is_array($config)) {
            throw new InvalidArgumentException('The [config] argument should be an array or null');
        }

        $this->active = $active = (bool) Arr::get($config, 'active' , false);

        if ($active) {
            $this->siteKey = Arr::get($config, 'siteKey');
            $this->secret  = Arr::get($config, 'secret');
        }
    }

    /**
     * Create a new ReCaptcha instance.
     *
     * @param array|null $config
     *
     * @return \Nova\Helpers\ReCaptcha
     */
    public static function make($config = null)
    {
        return new static($config);
    }

    /**
     * Compare given answer against the generated session.
     *
     * @param  string|null $response
     * @param  string|null $remoteIp
     * @return boolean
     */
    public static function check($response = null, $remoteIp = null)
    {
        return static::make()->verify($response, $remoteIp);
    }

    /**
     * Compare given answer against the generated session.
     *
     * @param  string|null $response
     * @param  string|null $remoteIp
     * @return boolean
     */
    public function verify($response = null, $remoteIp = null)
    {
        if (! $this->isActive()) {
            return true;
        }

        // Build the request parameters.
        $parameters = array(
            'secret'   => $this->getSecret(),
            'response' => $response ?: Request::input('g-recaptcha-response', ''),
            'remoteip' => $remoteIp ?: Request::ip(),
        );

        // Submit the POST request.
        $response = $this->submit($parameters);

        // Evaluate the Google server response.
        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        } else if (is_array($result) && isset($result['success'])) {
            return $result['success'];
        }

        return false;
    }

    /**
     * Submit the POST request with the specified parameters.
     *
     * @param  array $parameters
     * @return mixed
     */
    protected function submit(array $parameters)
    {
        $options = array(
            'http' => array(
                'header'    => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'    => 'POST',
                'content'   => http_build_query($parameters, '', '&'),
                // Force the peer validation to use www.google.com
                'peer_name' => 'www.google.com',
            ),
        );

        $context = stream_context_create($options);

        return file_get_contents(static::SITE_VERIFY_URL, false, $context);
    }

    /**
     * Get the Status
     *
     * @return string
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Get the Site Key
     *
     * @return string
     */
    public function getSiteKey()
    {
        return $this->siteKey;
    }

    /**
     * Get the Secret
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }
}
