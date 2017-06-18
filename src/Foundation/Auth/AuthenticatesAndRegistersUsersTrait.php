<?php

namespace Nova\Foundation\Auth;


trait AuthenticatesAndRegistersUsers
{
	use AuthenticatesUsersTrait, RegistersUsersTrait {
		AuthenticatesUsersTrait::redirectPath insteadof RegistersUsersTrait;
	}
}
