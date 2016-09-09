# MultiAuth For Laravel 5.1
---
## 安装

代码基于[Kbwebs/MultiAuth](https://github.com/Kbwebs/MultiAuth)完善！！！

```php
composer require zionon/multiauth "1.*"
```
## Authentication
Open up the config/app.php file and replace the AuthServiceProvider with:
```php
Illuminate\Auth\AuthServiceProvider::class -> Zionon\MultiAuth\AuthServiceProvider::class
```
And open config/auth.php file and remove:(如果搭配entrust使用的话，建议保留)
```PHP
'driver'  => 'eloquent'
'model'   => App\User::class,
'table'   => 'users'
```
and replace it with this array:
```PHP
'multi-auth' => [
     'user' => [
        'driver' => 'eloquent',
        'model'  => App\User::class
    ],
    'admin' => [
        'driver' => 'eloquent',
        'model'  => App\Admin::class
    ]
]
```
If you want to use Database instead of Eloquent you can use it as:
```PHP
'user' => [
    'driver' => 'database',
    'table'  => 'users'
]
```
## Password Resets
如果后台的不需要通过邮件重置密码的话(后台不建议开启邮件重置密码)，使用laravel自带的即可，使用的是config/auth配置中'multi-auth'数组的第一个认证driver。只需要把默认的app/Http/Controllers/Auth/PasswordController

```php
Illuminate\Foundation\Auth\ResetsPasswords  ->
Zionon\MultiAuth\Auth\ResetsPasswords
```

后台不需要邮件重置的密码的话，下面的可不需要做

Open up config/app.php file and replace the PasswordResetServiceProvider with:

```PHP
Illuminate\Auth\Passwords\PasswordResetServiceProvider::class -> Zionon\MultiAuth\PasswordResets\PasswordResetServiceProvider::class
```
If you  want to use the password resets from this Package you will need to change this in each Model there use password resets:
```PHP
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
```
to
```PHP
use Zionon\MultiAuth\PasswordResets\CanResetPassword;
use Zionon\MultiAuth\PasswordResets\Contracts\CanResetPassword as CanResetPasswordContract;
```
If you want to change the view for password reset for each auth type you can add this to the multi-auth array in config/auth.php:
```PHP
'email' => 'emails.users.password'
```
If you dont add this line, Laravel will automatically use the default path for emails.password like its defined in the password array.

To generate the password resets table you will need to run the following command:
```
php artisan zionon:multi-auth:create-resets-table
```
Likewise, if you want to clear all password resets, you have to run the following command:
```
php artisan zionon:multi-auth:clear-resets
```

**NOTE** It is very important that you replace the default service providers.
If you do not wish to use Password resets, then remove the original Password resets server provider as it will cause errors.

## Usage
#### Authentication:
直接使用trait即可

```php
use Zionon\MultiAuth\Auth\ThrottlesLogins;
use Zionon\MultiAuth\Auth\AuthenticatesAndRegistersUsers;
```

然后在控制器指定使用的model

`protected $authModel = 'user'`

其他属性和laravel自带的属性一样可以设置，新增的有

$loginView            登录显示文件

$registerView       注册显示文件

$remember          是否使用记住我功能

重点说一下多重登录，比如可以同时使用用户名，邮件，手机号码登录，暂时也只做了这三个，大部分情况应该也够用了。

属性$username为多重登录的关键，如果属性不设置或为字符串则是单认证登录，默认为email，可以改用户名或者手机号码.

如果$username为数组的话，则把登录认证的数组填进去，默认使用的name为authfield。如果自定义的话，则必须把authfield加入到数组中，否则会报错。



#### To do list

完善密码重置已经密码重置页面自定义

完善readme

写一个demo

完善多重登录

#### Password resets:

密码也改写了，用了trait

It works just like the original laravel authentication library,
the only change is the **user()** or **admin()** it will match the auth type, as your defining in the multi-auth array:
```
Password::sendResetLink($request->only('email'), function (Message $message) {
    $message->subject($this->getEmailSubject());
});
```
But now it has to be like, with the **user()** or **admin()**:
```
Password::user()->sendResetLink($request->only('email'), function (Message $message) {
    $message->subject($this->getEmailSubject());
});
```
Example for a password reset email:
```
Click here to reset your password: {{ URL::to('password/reset', array($type, $token)) }}.
```
This generates a URL like the following:
```
http://example.com/password/reset/user/21eb8ee5fe666r3b8d0521156bbf53266bnca572
```
Which will match the following route:
```
Route::get('password/reset/{type}/{token}', 'Controller@method');
```
#### Tip:
Remember to update all places where ex: Auth:: is been using, to ex: Auth::user() or what you have defined in config/auth.php