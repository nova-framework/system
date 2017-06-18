<?php

namespace Nova\Foundation\Auth;

use Nova\Auth\Contracts\Reminders\RemindableInterface;
use Nova\Auth\Contracts\UserInterface;
use Nova\Auth\Reminders\RemindableTrait;
use Nova\Auth\UserTrait;

use Nova\Database\ORM\Model;


class User extends Model implements UserInterface, RemindableInterface
{
	use UserTrait, RemindableTrait;
}

