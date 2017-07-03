<?php

namespace Nova\Foundation\Auth;

use Nova\Auth\Contracts\UserInterface;
use Nova\Auth\UserTrait;

use Nova\Database\ORM\Model;


class User extends Model implements UserInterface
{
	use UserTrait;
}

