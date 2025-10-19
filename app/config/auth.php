<?php

return [

    'defaults' => [
        'guard' => 'sanctum',
        'passwords' => 'managers',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'managers',
        ],
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'managers',
        ],
    ],

    'providers' => [
        'managers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Manager::class,
        ],
    ],

    'passwords' => [
        'managers' => [
            'provider' => 'managers',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
