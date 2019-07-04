<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;
use Nova\Broadcasting\BroadcastException;
use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Support\Facades\Config;
use Nova\Support\Arr;
use Nova\Support\Str;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client as HttpClient;


class QuasarBroadcaster extends Broadcaster
{
    /**
     * The application ID to access the Push Server.
     *
     * @var string
     */
    protected $publicKey;


    /**
     * The secret key to access the Push Server.
     *
     * @var string
     */
    protected $secretKey;

    /**
     * The options for connnecting to the Push Server.
     *
     * @var array
     */
    protected $options = array();


    /**
     * Create a new broadcaster instance.
     *
     * @param  \Nova\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container, array $config)
    {
        parent::__construct($container);

        //
        $this->publicKey = Arr::get($config, 'key');
        $this->secretKey = Arr::get($config, 'secret');

        $this->options = Arr::get($config, 'options', array());
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Nova\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse(Request $request, $result)
    {
        $channel = $request->input('channel_name');

        $socketId = $request->input('socket_id');

        if (! Str::startsWith($channel, 'presence-')) {
            $result = $this->socketAuth($channel, $socketId);
        } else {
            $user = $request->user();

            $result = $this->presenceAuth(
                $channel, $socketId, $user->getAuthIdentifier(), $result
            );
        }

        return json_decode($result, true);
    }

    /**
     * Creates a socket signature.
     *
     * @param string $socketId
     * @param string $customData
     *
     * @return string
     */
    protected function socketAuth($channel, $socketId, $customData = null)
    {
        if (preg_match('/^[-a-z0-9_=@,.;]+$/i', $channel) !== 1) {
            throw new BroadcastException('Invalid channel name ' .$channel);
        }

        //
        else if (preg_match('/^(?:\/[a-z0-9]+#)?[a-z0-9]+$/i', $socketId) !== 1) {
            throw new BroadcastException('Invalid socket ID ' .$socketId);
        }

        if (! is_null($customData)) {
            $auth = hash_hmac('sha256', $socketId .':' .$channel .':' .$customData, $this->secretKey, false);
        } else {
            $auth = hash_hmac('sha256', $socketId .':' .$channel, $this->secretKey, false);
        }

        $signature = compact('auth');

        // Add the custom data if it has been supplied.
        if (! is_null($customData)) {
            $signature['payload'] = $customData;
        }

        return json_encode($signature);
    }

    /**
     * Creates a presence signature (an extension of socket signing).
     *
     * @param string $socketId
     * @param string $userId
     * @param mixed  $userInfo
     *
     * @return string
     */
    protected function presenceAuth($channel, $socketId, $userId, $userInfo = null)
    {
        $userData = array('userId' => $userId);

        if (! is_null($userInfo)) {
            $userData['userInfo'] = $userInfo;
        }

        return $this->socketAuth($channel, $socketId, json_encode($userData));
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $socket = Arr::pull($payload, 'socket');

        $this->trigger(
            $this->formatChannels($channels), $event, $payload, $socket
        );
    }

    /**
     * Trigger an event by providing event name and payload.
     * Optionally provide a socket ID to exclude a client (most likely the sender).
     *
     * @param array|string $channels        A channel name or an array of channel names to publish the event on.
     * @param string       $event
     * @param mixed        $data            Event data
     * @param string|null  $socketId        [optional]
     *
     * @return bool
     * @throws \Nova\Broadcasting\BroadcastException
     */
    protected function trigger($channels, $event, $data, $socketId = null)
    {
        $event = str_replace('\\', '.', $event);

        $payload = array(
            'channels' => json_encode($channels),
            'event'    => $event,
            'data'     => json_encode($data),
            'socketId' => $socketId ?: '',
        );

        $path = sprintf('apps/%s/events', $this->publicKey);

        // Compute the hash.
        $content = "POST\n" .$path .':' .json_encode($payload);

        $hash = hash_hmac('sha256', $content, $this->secretKey, false);

        // Compute the server URL.
        $host = Arr::get($this->options, 'httpHost', '127.0.0.1');
        $port = Arr::get($this->options, 'httpPort', 2121);

        $url = $host .':' .$port .'/' .$path;

        return $this->executeHttpRequest($url, $payload, $hash);
    }

    /**
     * Execute a HTTP request to the Quasar webserver.
     *
     * @param string  $url
     * @param array  $payload
     * @param string  $hash
     *
     * @return bool
     * @throws \Nova\Broadcasting\BroadcastException
     */
    protected function executeHttpRequest($url, array $payload, $hash)
    {
        $client = new HttpClient();

        try {
            $response = $client->post($url, array(
                'headers' => array(
                    'CONNECTION'    => 'close',
                    'AUTHORIZATION' => 'Bearer ' .$hash,
                ),
                'body' => $payload,
            ));

            $status = (int) $response->getStatusCode();

            return ($status == 200) && ($response->getBody() == '200 OK');
        }
        catch (RequestException $e) {
            throw new BroadcastException($e->getMessage());
        }
    }
}
