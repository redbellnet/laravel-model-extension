<?php namespace RedBellNet\ModelExtension;

use Illuminate\Support\Facades\Redis;

trait RedisLib{
    /**
     * @Name redis
     * @Created by yuxuewen.
     * @Description redis入库函数
     * @param $key
     * @param $callback
     * @param int $expire
     * @return mixed
     */
    public static function redis($key, $callback, $expire = 0){
        if (!config('modelExtension.is_use_redis')) return $callback();

        $data = Redis::get($key);

        if ($data) {
            $data = unserialize($data);
            $data->is_from_cache = true;
        } else {
            $callbackData = $callback();

            if (is_object($callbackData) && get_class($callbackData) == 'Illuminate\Database\Eloquent\Collection') {
                if ($callbackData->isEmpty()) return $callbackData;
            } else {
                if (empty($callbackData)) return $callbackData;
            }

            Redis::set($key,serialize($callbackData));
            if ($expire){
                Redis::expire($key,$expire);
            }
            $data = $callbackData;
            $data->is_from_cache = false;
        }

        return $data;
    }

    /**
     * @Name RedisFlushByKey
     * @Created by yuxuewen.
     * @Description 删除redis中指定的key
     * @param $key
     * @return int|string
     */
    public static function RedisFlushByKey($key){
        $keys = self::getKeys($key);
        if (!empty($keys)){
            foreach ($keys as $k=>$v){
                $r = Redis::del($v);
                if (! $r)
                    return $v.' delete false';
            }
            return $keys;
        }

        return $keys;
    }

    /**
     * @Name getKeys
     * @Created by yuxuewen.
     * @Description 获取keys，或者检测key是否存在
     * @param $key
     * @param bool $is_addslashes
     * @return mixed
     */
    public static function getKeys($key, $is_addslashes = true){
        if ($is_addslashes)
            $key = addslashes($key);
        return Redis::keys($key);
    }

    public static function setData($key, $data, $function = 'set'){
        if (!config('modelExtension.is_use_redis')) return $data;
        if (is_array($data))
            Redis::{$function}($key, ...$data);
        else
            Redis::{$function}($key, $data);
    }

    public static function getData($key, $function  = 'get'){
        return Redis::{$function}($key);
    }

}
