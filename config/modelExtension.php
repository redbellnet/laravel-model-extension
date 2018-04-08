<?php

return [

    /*
     |--------------------------------------------------------------------------
     | is_use_redis Settings
     |--------------------------------------------------------------------------
     |
     | set 1 mean open redis, set 0 mean close redis.
     | default 1, you can set this item in .env file.
     |
     */

    'is_use_redis'      =>  env('IS_USE_REDIS', '1'),

    /*
     |--------------------------------------------------------------------------
     | query_flag_field Settings
     |--------------------------------------------------------------------------
     |
     | meaning query with 'query_flag_field'
     | example:
     |          where 'query_flag_field' = your database data
     |
     */
    'query_flag_field'  =>  '',
    'query_flag_field_request_required'  =>  true,

    /*
     |--------------------------------------------------------------------------
     | Put Field
     |--------------------------------------------------------------------------
     |
     | what field most possible updating in your table.
     | example:
     | 'put_field' =>  [ 'user' => ['username', 'password'] ]
     */
    'put_field' =>  [],

    'normal_status'     => [1,2],
    'del_status'        =>  0,
    'del_status_arr'    =>  [0],
    'use_status'        =>  1,
    'use_status_arr'    =>  [1],
    'stop_status'       =>  2,
    'stop_status_arr'   =>  [2],
    'rule_all_status'   => [0,1,2],
];
