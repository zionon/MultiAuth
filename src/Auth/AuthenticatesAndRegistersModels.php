<?php

namespace Zionon\MultiAuth\Auth;

use Illuminate\Support\Facades\Auth;

trait AuthenticatesAndRegistersModels
{
    public function getAuthModel()
    {
        return property_exists($this, 'authModel') ? $this->authModel : 'user';
    }

    public function getAuthManager()
    {
        $authModel = $this->getAuthModel();
        return Auth::$authModel();
    }
}