<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;
use Nova\Broadcasting\BroadcastException;
use Nova\Http\Request;
use Nova\Support\Str;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Pusher;


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
     * @param  \Pusher  $pusher
     * @return void
     */
    public function __construct(Pusher $pusher)
    {
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
        $user = $request->user();

        $channel = $request->input('channel_name');

        if (Str::startsWith($channel, array('private-', 'presence-')) && is_null($user)) {
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
                $channel, $socketId, $user->getAuthIndentifier(), $result
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

        $response = $this->pusher->trigger($channels, $event, $payload, $socket, true);

        //
        $status = is_array($response) ? $response['status'] : 200;

        if ((($status >= 200) && ($status <= 299)) || ($response === true)) {
            return;
        }

        throw new BroadcastException(
            is_bool($response) ? 'Failed to connect to Pusher.' : $response['body']
        );
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
