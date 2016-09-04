<?php

namespace Zionon\MultiAuth\Auth;

trait AuthenticatesAndRegistersUsers
{
    use AuthenticatesUsers, RegistersUsers {
        AuthenticatesUsers::redirectPath insteadof RegistersUsers;
        AuthenticatesUsers::getAuthModel insteadof RegistersUsers;
        AuthenticatesUsers::getAuthManager insteadof RegistersUsers;
    }
}
