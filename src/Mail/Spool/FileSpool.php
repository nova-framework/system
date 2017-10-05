<?php

namespace Nova\Mail\Spool;

use Swift_FileSpool as BaseSpool;


class DatabaseSpool extends BaseSpool
{

    /**
     * Remove from the Queue the messages failed to be sent.
     */
    public function clearFailedMessages()
    {
        //
    }
}
