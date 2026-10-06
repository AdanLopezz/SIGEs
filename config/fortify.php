<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'auth_middleware' => 'auth',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',
    'views' => true,
    'home' => '/dashboard',
    // Normalize safely in middleware/action, before validation and throttling.
    'lowercase_usernames' => false,
    'limiters' => ['login' => 'login'],
    'redirects' => ['login' => '/dashboard', 'register' => '/dashboard', 'logout' => '/login'],
    'features' => [Features::registration()],
];
