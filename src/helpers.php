<?php

if (!function_exists('normal_status_arr')) {
    function normal_status_arr()
    {
        return config('modelExtension.normal_status');
    }
}

if (!function_exists('use_status')) {
    function use_status()
    {
        return config('modelExtension.use_status');
    }
}

if (!function_exists('use_status_arr')) {
    function use_status_arr()
    {
        return config('modelExtension.use_status_arr');
    }
}

if (!function_exists('stop_status')) {
    function stop_status()
    {
        return config('modelExtension.stop_status');
    }
}

if (!function_exists('stop_status_arr')) {
    function stop_status_arr()
    {
        return config('modelExtension.stop_status_arr');
    }
}

if (!function_exists('rule_all_status')) {
    function rule_all_status()
    {
        return config('modelExtension.rule_all_status');
    }
}
