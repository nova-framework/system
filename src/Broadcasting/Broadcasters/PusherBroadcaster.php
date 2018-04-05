<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;
use Nova\Broadcasting\BroadcastException;
use Nova\Container\Container;
use Nova\Http\Request;
use Nova\Support\Str;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Pusher\Pusher;


class PusherBroadcaster extends Broadcaster
{
    /**
     * The Pusher SDK instance.
     *
     * @var \Pusher
     */
    protected $pusher;


    /**
     * Create a new broadcaster instance.
     *
     * @param  \Nova\Container\Container  $container
     * @param  \Pusher  $pusher
     * @return void
     */
    public function __construct(Container $container, Pusher $pusher)
    {
        parent::__construct($container);

        //
        $this->pusher = $pusher;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Nova\Http\Request  $request
     * @return mixed
     */
    public function authenticate(Request $request)
    {
        $channelName = $request->input('channel_name');

        $channel = preg_replace('/^(private|presence)\-/', '', $channelName, 1, $count);

        if (($count == 1) && is_null($request->user())) {
            throw new AccessDeniedHttpException;
        }

        return $this->verifyUserCanAccessChannel($request, $channel);
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

        if (Str::startsWith($channel, 'private')) {
            $result = $this->pusher->socket_auth($channel, $socketId);
        } else {
            $user = $request->user();

            $result = $this->pusher->presence_auth(
                $channel, $socketId, $user->getAuthIdentifier(), $result
            );
        }

        return json_decode($result, true);
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $socket = Arr::pull($payload, 'socket');

        $response = $this->pusher->trigger($this->formatChannels($channels), $event, $payload, $socket, true);

        if (($response['status'] >= 200) && ($response['status'] <= 299)) {
            return;
        }

        throw new BroadcastException($response['body']);
    }

    /**
     * Get the Pusher SDK instance.
     *
     * @return \Pusher
     */
    public function getPusher()
    {
        return $this->pusher;
    }
}
