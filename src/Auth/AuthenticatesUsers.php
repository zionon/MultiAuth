<?php

namespace Zionon\MultiAuth\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;

trait AuthenticatesUsers
{
    use RedirectsUsers, AuthenticatesAndRegistersModels;

    /**
     * Show the application login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogin()
    {
        return view(property_exists($this, 'loginView') ? $this->loginView : 'auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [
            $this->loginUsername() => 'required', 'password' => 'required',
        ]);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        $throttles = $this->isUsingThrottlesLoginsTrait();

        if ($throttles && $this->hasTooManyLoginAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        $credentials = $this->getCredentials($request);

        // var_dump($credentials);die;

        if ($this->getAuthManager()->attempt($credentials, $request->has('remember'))) {
            return $this->handleUserWasAuthenticated($request, $throttles);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        if ($throttles) {
            $this->incrementLoginAttempts($request);
        }

        return redirect($this->loginPath())
            ->withInput($request->only($this->loginUsername(), 'remember'))
            ->withErrors([
                $this->loginUsername() => $this->getFailedLoginMessage(),
            ]);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $throttles
     * @return \Illuminate\Http\Response
     */
    protected function handleUserWasAuthenticated(Request $request, $throttles)
    {
        if ($throttles) {
            $this->clearLoginAttempts($request);
        }

        if (method_exists($this, 'authenticated')) {
            return $this->authenticated($request, $this->getAuthManager()->get());
        }

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getCredentials(Request $request)
    {
        $loginArray = $request->only($this->loginUsername(), 'password');
        if (! $this->isUserMulti()) {
            return $loginArray;
        }

        return $this->loginArray($loginArray);        
    }

    /**
     * Get the failed login message.
     *
     * @return string
     */
    protected function getFailedLoginMessage()
    {
        return Lang::has('auth.failed')
                ? Lang::get('auth.failed')
                : 'These credentials do not match our records.';
    }

    /**
     * Log the user out of the application.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogout(Request $request)
    {
        if ($this->getRemember() == 'true') {
            Auth::logout();
        } else {
            $request->session()->flush();
        }

        return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : '/');
    }

    /**
     * Get the path to the login route.
     *
     * @return string
     */
    public function loginPath()
    {
        return property_exists($this, 'loginPath') ? $this->loginPath : '/auth/login';
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function loginUsername()
    {
        if (! $this->isUserMulti()) {
            return property_exists($this, 'username') ? $this->username : 'email';
        }

        if (! array_key_exists('authfield', $this->username)) {
            return 'authfield';
        }

        return $this->username['authfield'];
        // return property_exists($this, 'username') ? $this->username : 'email';
    }

    /**
     * Determine if the class is using the ThrottlesLogins trait.
     *
     * @return bool
     */
    protected function isUsingThrottlesLoginsTrait()
    {
        return in_array(
            ThrottlesLogins::class, class_uses_recursive(get_class($this))
        );
    }

    protected function getRemember()
    {
        return property_exists($this, 'remember') ? $this->remember : 'true';
    }

    protected function isUserMulti()
    {
        if (property_exists($this, 'username') && is_array($this->username)) {
            return true;
        }
        return false;
    }

    protected function loginArray(Array $oldArray)
    {
        $authstring = $oldArray[$this->loginUsername()];
        $newArray = [];
        if (strpos($authstring, '@')) {
            $newArray['email'] = $authstring;
        } else if (preg_match("/^1[34578]\d{9}$/", $authstring)) {
            $newArray['phone'] = $authstring;
        } else {
            $newArray['name'] = $authstring;
        }
        $newArray['password'] = $oldArray['password'];
        unset($oldArray);
        return $newArray;
    }
}
