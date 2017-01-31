<?php

namespace Nova\Widget\Exceptions;

use Exception;


class InvalidWidgetException extends Exception
{
    protected $message = 'Widget class must be an instance of Nova\Widget\Widget';
}
