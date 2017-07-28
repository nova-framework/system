<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;
use Nova\Broadcasting\BroadcastException;
use Nova\Support\Arr;
use Nova\Support\Str;

use Symfony\Component\HttpKernel\Exception\HttpException;

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
    public function authenticate($request)
    {
        $channelName = $request->input('channel_name');

        //
        $count = 0;

        $channel = preg_replace('/^(private|presence)\-/', '', $channelName, -1, $count);

        if (($count === 1) && is_null($user = $request->user())) {
            throw new HttpException(403);
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
    public function validAuthenticationResponse($request, $result)
    {
        $channel = $request->input('channel_name');

        $socketId = $request->input('socket_id');

        if (Str::startsWith($channel, 'private')) {
            return $this->decodePusherResponse(
                $this->pusher->socket_auth($channel, $socketId)
            );
        }

        $authId = $request->user()->getKey();

        return $this->decodePusherResponse(
            $this->pusher->presence_auth($channel, $socketId, $authId, $result)
        );
    }

    /**
     * Decode the given Pusher response.
     *
     * @param  mixed  $response
     * @return array
     */
    protected function decodePusherResponse($response)
    {
        return json_decode($response, true);
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $socket = Arr::pull($payload, 'socket');

        $response = $this->pusher->trigger($this->formatChannels($channels), $event, $payload, $socket, true);

        //
        $status = is_array($response) ? $response['status'] : 0;

        if ((($status >= 200) && ($status <= 299)) || ($response === true)) {
            return;
        }

        throw new BroadcastException(
            is_array($response) ? $response['body'] : 'Failed to connect to Pusher.'
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
