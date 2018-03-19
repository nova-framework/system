<?php

namespace Nova\Notification\Models;

use Nova\Database\ORM\Collection;


class NotificationCollection extends Collection
{
    /**
     * Mark all notification as read.
     *
     * @return void
     */
    public function markAsRead()
    {
        $this->each(function ($notification)
        {
            $notification->markAsRead();
        });
    }
}
