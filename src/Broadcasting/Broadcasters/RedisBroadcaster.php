<?php

namespace Nova\Broadcasting\Broadcasters;

use Nova\Broadcasting\Broadcaster;
use Nova\Http\Request;
use Nova\Redis\Database as RedisDatabase;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class RedisBroadcaster implements Broadcaster
{
    /**
     * The Redis instance.
     *
     * @var \Nova\Contracts\Redis\Database
     */
    protected $redis;

    /**
     * The Redis connection to use for broadcasting.
     *
     * @var string
     */
    protected $connection;


    /**
     * Create a new broadcaster instance.
     *
     * @param  \Nova\Contracts\Redis\Database  $redis
     * @param  string  $connection
     * @return void
     */
    public function __construct(RedisDatabase $redis, $connection = null)
    {
        $this->redis = $redis;

        $this->connection = $connection;
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
        if (is_bool($result)) {
            return json_encode($result);
        }

        $user = $request->user();

        return json_encode(array(
            'channel_data' => array(
                'user_id'   => $user->getAuthIdentifier(),
                'user_info' => $result,
            ),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = array())
    {
        $connection = $this->redis->connection($this->connection);

        $payload = json_encode(array(
            'event' => $event,
            'data'  => $payload
        ));

        foreach ($channels as $channel) {
            $connection->publish($channel, $payload);
        }
    }
 }
