<?php

namespace Nova\Notifications;

use Nova\Foundation\Application;
use Nova\Support\Manager;

use Nova\Notifications\Channels\BroadcastChannel;
use Nova\Notifications\Channels\DatabaseChannel;
use Nova\Notifications\Channels\MailChannel;
use Nova\Notifications\DispatcherInterface;
use Nova\Notifications\NotificationSender;

use InvalidArgumentException;


class ChannelManager extends Manager implements DispatcherInterface
{
    /**
     * The notifications sender instance.
     *
     * @var \Nova\Notifications\NotificationSender
     */
    protected $sender;

    /**
     * The default channels used to deliver messages.
     *
     * @var array
     */
    protected $defaultChannel = 'mail';


    /**
     * Create a new manager instance.
     *
     * @param  \Nova\Foundation\Application  $app
     * @param  \Nova\Notifications\NotificationSender  $sender
     * @return void
     */
    public function __construct(Application $app, NotificationSender $sender)
    {
        $this->app = $app;

        $this->sender = $sender;
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
        $this->sender->send($notifiables, $notification);
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
        $this->sender->sendNow($notifiables, $notification, $channels);
    }

    /**
     * Get a channel instance.
     *
     * @param  string|null  $name
     * @return mixed
     */
    public function channel($name = null)
    {
        return $this->driver($name);
    }

    /**
     * Create an instance of the database driver.
     *
     * @return \Nova\Notifications\Channels\DatabaseChannel
     */
    protected function createDatabaseDriver()
    {
        return $this->app->make(DatabaseChannel::class);
    }

    /**
     * Create an instance of the broadcast driver.
     *
     * @return \Nova\Notifications\Channels\BroadcastChannel
     */
    protected function createBroadcastDriver()
    {
        return $this->app->make(BroadcastChannel::class);
    }

    /**
     * Create an instance of the mail driver.
     *
     * @return \Nova\Notifications\Channels\MailChannel
     */
    protected function createMailDriver()
    {
        return $this->app->make(MailChannel::class);
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        }
        catch (InvalidArgumentException $e) {
            if (class_exists($driver)) {
                return $this->app->make($driver);
            }

            throw $e;
        }
    }

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->defaultChannel;
    }

    /**
     * Get the default channel driver name.
     *
     * @return string
     */
    public function deliversVia()
    {
        return $this->defaultChannel;
    }

    /**
     * Set the default channel driver name.
     *
     * @param  string  $channel
     * @return void
     */
    public function deliverVia($channel)
    {
        $this->defaultChannel = $channel;
    }
}
