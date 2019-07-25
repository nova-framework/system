<?php

namespace Nova\Notifications;

use Nova\Bus\Dispatcher as BusDispatcher;
use Nova\Database\ORM\Collection as ModelCollection;
use Nova\Events\Dispatcher as EventDispatcher;
use Nova\Foundation\Application;
use Nova\Queue\ShouldQueueInterface;
use Nova\Support\Collection;

use Nova\Notifications\Events\NotificationSending;
use Nova\Notifications\Events\NotificationSent;
use Nova\Notifications\DispatcherInterface;
use Nova\Notifications\SendQueuedNotifications;

use Ramsey\Uuid\Uuid;

use InvalidArgumentException;


class NotificationSender
{
    /**
     * The notification manager instance.
     *
     * @var \Nova\Notifications\ChannelManager
     */
    protected $manager;

    /**
     * The events dispatcher instance.
     *
     * @var \Nova\Events\Dispatcher
     */
    protected $events;

    /**
     * The command bus dispatcher instance.
     *
     * @var \Nova\Bus\Dispatcher
     */
    protected $bus;


    /**
     * Create a new notification sender instance.
     *
     * @param  \Nova\Notifications\ChannelManager  $manager
     * @param  \Nova\Events\Dispatcher  $events
     * @param  \Nova\Bus\Dispatcher  $bus
     * @return void
     */
    public function __construct(ChannelManager $manager, EventDispatcher $events, BusDispatcher $bus)
    {
        $this->manager = $manager;
        $this->events  = $events;
        $this->bus     = $bus;
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  \Nova\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @return void
     */
    public function send($notifiables, $notification)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        if (! $notification instanceof ShouldQueueInterface) {
            return $this->sendNow($notifiables, $notification);
        }

        foreach ($notifiables as $notifiable) {
            $notificationId = Uuid::uuid4()->toString();

            foreach ($notification->via($notifiable) as $channel) {
                $this->queueToNotifiable($notifiable, $notificationId, clone $notification, $channel);
            }
        }
    }

    /**
     * Send the given notification to the given notifiable entities.
     *
     * @param  \Nova\Support\Collection|array|mixed  $notifiables
     * @param  mixed  $notification
     * @param  array|null  $channels
     * @return void
     */
    public function sendNow($notifiables, $notification, array $channels = null)
    {
        $notifiables = $this->formatNotifiables($notifiables);

        $original = clone $notification;

        foreach ($notifiables as $notifiable) {
            if (empty($viaChannels = $channels ?: $notification->via($notifiable))) {
                continue;
            }

            $notificationId = Uuid::uuid4()->toString();

            foreach ((array) $viaChannels as $channel) {
                $this->sendToNotifiable($notifiable, $notificationId, clone $original, $channel);
            }
        }
    }

    /**
     * Send the given notification to the given notifiable via a channel.
     *
     * @param  mixed  $notifiable
     * @param  string  $id
     * @param  mixed  $notification
     * @param  string  $channel
     * @return void
     */
    protected function sendToNotifiable($notifiable, $id, $notification, $channel)
    {
        if (is_null($notification->id)) {
            $notification->id = $id;
        }

        if ($this->shouldSendNotification($notifiable, $notification, $channel)) {
            $response = $this->driver($channel)->send($notifiable, $notification);

            $this->events->dispatch(
                new NotificationSent($notifiable, $notification, $channel, $response)
            );
        }
    }

    /**
     * Determines if the notification can be sent.
     *
     * @param  mixed  $notifiable
     * @param  mixed  $notification
     * @param  string  $channel
     * @return bool
     */
    protected function shouldSendNotification($notifiable, $notification, $channel)
    {
        $result = $this->events->until(
            new NotificationSending($notifiable, $notification, $channel)
        );

        return ($result !== false);
    }

    /**
     * Queue the given notification to the given notifiable via a channel.
     *
     * @param  mixed  $notifiable
     * @param  string  $id
     * @param  mixed  $notification
     * @param  string  $channel
     * @return void
     */
    protected function queueToNotifiable($notifiable, $id, $notification, $channel)
    {
        $notification->id = $id;

        $job = with(new SendQueuedNotifications($notifiable, $notification, array($channel)))
            ->onConnection($notification->connection)
            ->onQueue($notification->queue)
            ->delay($notification->delay);

        $this->bus->dispatch($job);
    }

    /**
     * Format the notifiables into a Collection / array if necessary.
     *
     * @param  mixed  $notifiables
     * @return ModelCollection|array
     */
    protected function formatNotifiables($notifiables)
    {
        if ((! $notifiables instanceof Collection) && ! is_array($notifiables)) {
            $items = array($notifiables);

            if ($notifiables instanceof Model) {
                return new ModelCollection($items);
            }

            return $items;
        }

        return $notifiables;
    }
}
