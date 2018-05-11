<?php

namespace Nova\Notifications;

use Nova\Support\Facades\Config;
use Nova\Support\Facades\Notification as NotificationFacade;
use Nova\Support\Str;


trait NotifiableTrait
{
    /**
     * Get the entity's notifications.
     */
    public function notifications()
    {
        return $this->morphMany('Nova\Notifications\Models\Notification', 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the entity's read notifications.
     */
    public function readNotifications()
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->whereNull('read_at');
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $instance
     * @return void
     */
    public function notify($instance)
    {
        return NotificationFacade::send(array($this), $instance);
    }

    /**
     * Get the notification routing information for the given driver.
     *
     * @param  string  $driver
     * @return mixed
     */
    public function routeNotificationFor($driver)
    {
        $method = 'routeNotificationFor'. Str::studly($driver);

        if (method_exists($this, $method)) {
            return call_user_func(array($this, $method));
        }

        // No custom method for routing the notifications.
        else if ($driver == 'database') {
            return $this->notifications();
        }

        // Finally, we will accept only the mail driver.
        else if ($driver != 'mail') {
            return null;
        }

        // If the email field is like: admin@novaframework.dev
        if (preg_match('/^\w+@\w+\.dev$/s', $this->email) === 1) {
            return Config::get('mail.from.address');
        }

        return $this->email;
    }

}