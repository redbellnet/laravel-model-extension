<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Is Use Settings
     |--------------------------------------------------------------------------
     |
     | set 1 mean open redis, set 0 mean close redis.
     | default 1, you can set this item in .env file.
     |
     */

    'is_use_redis'      =>  env('IS_USE_REDIS', '1'),

    /*
     |--------------------------------------------------------------------------
     | Put Field
     |--------------------------------------------------------------------------
     |
     | what field most possible updating in your table.
     | example:
     | 'put_field' =>  [ 'user' => ['username', 'password'] ]
     */
    'put_field' =>  []
];
