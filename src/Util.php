<?php namespace RedBellNet\ModelExtension;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Util
{

    /**
     * @Name query_flag_field_for_redis_key
     * @Created by yuxuewen.
     * @Description 若有查询标志字段，则加入相应的redis的key中
     * @param $redis_key
     * @return string
     */
    public static function query_flag_field_for_redis_key($redis_key){
        if (!empty($query_flag_field = self::query_flag_field())){
            $redis_key .= '_by_'.$query_flag_field[0].'_'.$query_flag_field[1];
        }
        return $redis_key;
    }

    /**
     * @Name is_set_status
     * @Created by yuxuewen.
     * @Description 是否存在status字段处理
     * @param $status
     * @param array $where
     * @return array
     */
    public static function is_set_status($status){
        if (!empty($status)){
            if (is_array($status)){
                return ['status',$status];
            } else {
                return ['status',call_user_func($status)];
            }
        }  else {
            return [];
        }

    }

    /**
     * @Name set_order_by
     * @Created by yuxuewen.
     * @Description 设置排序字段
     * @param array $order_by
     * @return mixed
     */
    public static function set_order_by($order_by=[]){
        self::$order_by = $order_by;
        return static::getModel();
    }

    /**
     * @Name handle_columns
     * @Created by yuxuewen.
     * @Description 空数组或者是*的查询字段，自动读取model里面定义的字段
     * @param array $columns
     * @return array
     */
    protected static function handle_columns(array $columns){
        if (empty($columns) || $columns[0] == '*'){
            $model = static::getModel();
            return array_keys($model->attributes());
        } else {
            return $columns;
        }
    }

    /**
     * @Name handle_base_where_function
     * @Created by yuxuewen.
     * @Description
     * @param Model $model
     * @param array $where
     * @param string $where_function
     * @return Model
     */
    protected static function handle_base_where_function(Builder $model, array $where, $where_function = 'where'){

        foreach ($where as $k => $v) {
            if ($v instanceOf \Closure) {
                $model = $model->{$k}($v);
            } else if (is_array($v) && $where_function == 'where') {

                $model = $model->where($k, $v[0], $v[1]);

            } else {
                $model = $model->{$where_function}($k, $v);
            }
        }
        return $model;
    }

    /**
     * @Name handle_array_where_function
     * @Created by yuxuewen.
     * @Description
     * @param Model $model
     * @param $where_function
     * @return Model
     */
    protected static function handle_array_where_function(Builder $model, $where_function){

        foreach ($where_function as $k => $v) {
            foreach ($v as $k_k => $v_v)
                $model = $model->{$k}($k_k, $v_v);
        }
        return $model;
    }
    
}