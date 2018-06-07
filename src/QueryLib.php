<?php namespace RedBellNet\ModelExtension;

use Illuminate\Support\Facades\Log;
use RedBellNet\ModelExtension\Event\HandleModelEvent;
use RedBellNet\ModelExtension\Util;
use RedBellNet\ModelExtension\RedisLib;

trait QueryLib{
    use Util, RedisLib;

    protected static $is_open_query_flag_field = true;
    protected static $order_by=[];
    protected static $group_by=[];

    /**
     * @Name order_by
     * @Created by yuxuewen.
     * @Description
     * @param array $order_by
     */
    protected static function order_by($order_by=[]){
        self::$order_by = $order_by;
    }

    /**
     * @Name group_by
     * @Created by yuxuewen.
     * @Description
     * @param array $group_by
     */
    protected static function group_by($group_by=[]){
        self::$group_by = $group_by;
    }

    /**
     * @Name set_is_open_query_flag_field
     * @Created by yuxuewen.
     * @Description
     * @param bool $bool
     */
    protected static function set_is_open_query_flag_field($bool = true){
        static::$is_open_query_flag_field = $bool;

    }

    /**
     * @Name arrayChangeToOrWhere
     * @Created by yuxuewen.
     * @Description
     * @param $query
     * @param array $normal_array
     */
    protected static function arrayChangeToOrWhere($query, array $normal_array){
        if (!empty($normal_array)){
            foreach ($normal_array as $k => $v){
                $query->orWhere($k, 'like', $v);
            }
        }
    }

    /**
     * @Name query_flag_field
     * @Created by yuxuewen.
     * @Description
     * @return array
     */
    protected static function query_flag_field(){
        $query_flag_field = config('modelExtension.query_flag_field');

        if (self::$is_open_query_flag_field && $query_flag_field && $query_flag_field_value = call_user_func(config('modelExtension.query_flag_field'))) {
            return [$query_flag_field,$query_flag_field_value];
        }
        self::$is_open_query_flag_field = true;
        return [];
    }

    protected static function handle_get_by_id_data_to_redis($data){
        if (!empty($data)){
            if (!$data->is_from_cache){
                $search_keys = [];

                $put_field = config('modelExtension.put_field');
                $model = last(explode("\\",static::class));
                if (!empty($put_field)){
                    foreach ($put_field as $k=>$v){
                        if ($k == $model){
                            $search_keys = $v;
                            break;
                        }
                    }
                }

                $redis_search_keys = self::getData(self::query_flag_field_for_redis_key(static::class.'_create_fields'), 'smembers');

                $search_keys = array_merge($search_keys, $redis_search_keys);

                if (!empty($search_keys)){
                    foreach ($search_keys as $k=>$v){
                        self::setData(self::query_flag_field_for_redis_key(static::class.'_field_'.$v.'_'.$data->$v), $data->id, 'sadd');
                    }
                }
            }
        }
    }

    protected static function handle_join_table_with_model_relation($join_table, $join_table_result, $model_id){
        $search_keys = [];

        $put_field = config('modelExtension.put_field');
        $model_relation = $join_table_result->getRelation($join_table);
        $model_namespace = get_class($join_table_result->getRelation($model_relation));
        $model = last(explode("\\",$model_namespace));
        if (!empty($put_field)){
            foreach ($put_field as $k=>$v){
                if ($k == $model){
                    $search_keys = $v;
                    break;
                }
            }
        }

        $redis_search_keys = self::getData(self::query_flag_field_for_redis_key($model_namespace.'_create_fields'), 'smembers');

        $search_keys = array_merge($search_keys, $redis_search_keys);

        $redis_select_field = [];
        if (!empty($model_relation)){
            $original = $model_relation->getOriginal();
            unset($original['id']);
            $search_keys = array_merge(array_keys($original));
        }

        if (!empty($search_keys)){
            foreach ($search_keys as $k=>$v){
                if (!empty($model_relation) && isset($model_relation[$v])){
                    self::setData(self::query_flag_field_for_redis_key($model_namespace.'_field_'.$v.'_'.$model_relation[$v]), $model_relation['id'], 'sadd');
                }

            }
        }

        self::setData(self::query_flag_field_for_redis_key($join_table.'_id_'.$join_table_result[$join_table]['id'].'_join_table_with_model_relation'), static::class.'_'.$model_id, 'sadd');
    }

    /**
     * @Name checkExist
     * @Created by yuxuewen.
     * @Description
     * @param $field
     * @param $value
     * @param array $status
     * @param array $other_where
     * @param array $return_field
     * @return \Closure
     */
    protected static function checkExist($field, $value, array $status = [], array $other_where = [], array $return_field = [], $where_function='where'){
        return function () use($field, $value, $status, $other_where, $return_field, $where_function){
            $self = static::where([$field => $value]);


            if (!empty($other_where)) {
                event(new HandleModelEvent($self, $other_where, $where_function));
            }

            if (!empty($status))
                $self = $self->whereIn(...$status);

            if (!empty($query_flag_field = self::query_flag_field()))
                $self = $self->where(...$query_flag_field);



            return $self->get(array_merge([$field],$return_field));
        };
    }

    /**
     * @Name basePut
     * @Created by yuxuewen.
     * @Description 更新基础类
     * @param array $field_where
     * @param array $field_value
     * @param array $status
     * @return mixed
     */
    protected static function basePut(array $field_where, array $field_value, array $status = []){
        $self = static::getModel();
        if (!empty($field_where)){
            foreach ($field_where as $k => $v){
                if (is_array($v)){
                    $self = $self->where($k, $v[0], $v[1]);
                } else {
                    $self = $self->where($k, $v);
                }
            }
        }

        if (!empty($query_flag_field = self::query_flag_field()))
            $self = $self->where($query_flag_field[0],$query_flag_field[1]);

        if (!empty($status))
            $self = $self->whereIn($status[0],$status[1]);

        return $self->update($field_value);
    }

    /**
     * @Name baseGet
     * @Created by yuxuewen.
     * @Description
     * @param array $columns
     * @param array $status
     * @param string $where_function
     * @param string $query_function
     * @param array $joinTable
     * @return \Closure
     */
    protected static function baseGet(array $columns = ['*'], array $status = [], $where_function = 'where', $query_function= '', array $joinTable = [] ){
        return function () use($status, $joinTable, $columns, $where_function, $query_function) {
            $self = static::getModel();


            if (!empty($query_flag_field = self::query_flag_field()))
                $self = $self->where($query_flag_field[0], $query_flag_field[1]);

            if (!empty($status))
                $self = $self->whereIn($status[0],$status[1]);

            if (!empty($order_by_arr = self::$order_by)){
                foreach ($order_by_arr as $k => $v){
                    $self = $self->orderBy($k, $v);
                }
                self::$order_by = [];
            }

            if (!empty($group_by_arr = self::$group_by)){
                foreach ($group_by_arr as $k => $v){
                    $self = $self->groupBy($k, $v);
                }
                self::$group_by = [];
            }

            if (!empty($joinTable))
                $self = $self->with($joinTable);

            if (empty($query_function))
                return $self->get(self::handle_columns($columns));
            else
                return $self->{$query_function}(self::handle_columns($columns));
        };
    }

    /**
     * @Name baseGetJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param array $joinTable
     * @param array $columns
     * @param array $status
     * @param string $where_function
     * @param string $query_function
     * @return \Closure
     */
    protected static function baseGetJoinTable(array $joinTable = [], array $columns = ['*'], array $status = [], $where_function = 'where', $query_function= '' ){
        return self::baseGet($columns, $status , $where_function, $query_function, $joinTable);
    }



    /**
     * @Name baseGetByField
     * @Created by yuxuewen.
     * @Description
     * @param array $where
     * @param array $status
     * @param array $columns
     * @param string $where_function
     * @param string $query_function
     * @param array $joinTable
     * @return \Closure
     */
    protected static function baseGetByField(array $where, array $status = [], array $columns = ['*'], $where_function = 'where', $query_function= '', array $joinTable = [] ){
        return function () use($where, $status, $joinTable, $columns, $where_function, $query_function) {
            $self = static::setModel(static::getModel());

            if (!empty($where)) {
                event(new HandleModelEvent($self, $where, $where_function));
            }

            if (!empty($query_flag_field = self::query_flag_field()))
                $self = $self->where($query_flag_field[0], $query_flag_field[1]);

            if (!empty($status))
                $self = $self->whereIn($status[0],$status[1]);

            if (!empty($order_by_arr = self::$order_by)){
                foreach ($order_by_arr as $k => $v){
                    $self = $self->orderBy($k, $v);
                }
                self::$order_by = [];
            }

            if (!empty($group_by_arr = self::$group_by)){
                foreach ($group_by_arr as $k => $v){
                    $self = $self->groupBy($k, $v);
                }
                self::$group_by = [];
            }

            if (!empty($joinTable))
                $self = $self->with($joinTable);

            if (empty($query_function))
                return $self->get(self::handle_columns($columns));
            else
                return $self->{$query_function}(self::handle_columns($columns));
        };
    }

    /**
     * @Name baseGetByFieldJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param array $where
     * @param array $joinTable
     * @param array $status
     * @param array $columns
     * @param string $where_function
     * @param string $query_function
     * @return \Closure
     */
    protected static function baseGetByFieldJoinTable(array $where, array $joinTable = [], array $status = [], array $columns = ['*'], $where_function = 'where', $query_function= '' ){
        return self::baseGetByField($where, $status, $columns, $where_function, $query_function,$joinTable );
    }

    /**
     * @Name baseGetByFieldWithPageJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param string $perPage
     * @param string $page
     * @param array $where
     * @param array $joinTable
     * @param array $status
     * @param array $columns
     * @param string $where_function
     * @return \Closure
     */
    protected static function baseGetByFieldWithPageJoinTable($perPage = '', $page = '', array $where, array $joinTable = [], array $status = [], array $columns = ['*'], $where_function = 'where'){
        return self::baseGetListWithPage($perPage, $page, $where, $status, $columns, $where_function, $joinTable);
    }

    /**
     * @Name baseGetByID
     * @Created by yuxuewen.
     * @Description
     * @param $id
     * @param array $status
     * @param array $where
     * @param array $columns
     * @param array $joinTable
     * @return \Closure
     */
    protected static function baseGetByID($id, array $status = [], array $where = [], array $columns = array('*'), array $joinTable = [] ){

        return function () use($id, $status, $joinTable, $where, $columns) {
            $self = static::setModel(static::getModel());
            $self = $self->whereKey($id);
            if (!empty($where)) {
                foreach ($where as $k => $v) {
                    if (is_array($v)) {
                        $self = $self->where($k, $v[0], $v[1]);
                    } else {
                        $self = $self->where($k, $v);
                    }
                }
            }

            if (!empty($query_flag_field = self::query_flag_field()))
                $self = $self->where($query_flag_field[0], $query_flag_field[1]);

            if (!empty($status))
                $self = $self->whereIn($status[0],$status[1]);

            if (!empty($joinTable)){
                $self = $self->with($joinTable);
            }


            if (is_array($id))
                $result =  $self->get(self::handle_columns($columns));
            else
                $result =  $self->first(self::handle_columns($columns));

            if (!empty($joinTable)){
                foreach ($joinTable as $k=>$v){
//                    self::handle_join_table_with_model_relation($k, $result, $id);
                }
            }
//            $original = $result->getRelation('users')->getOriginal();
//            unset($original['id']);
//            dd(array_keys($original));

            return $result;
        };
    }


    /**
     * @Name baseGetListWithPage
     * @Created by yuxuewen.
     * @Description
     * @param string $perPage
     * @param string $page
     * @param array $where
     * @param array $status
     * @param array $columns
     * @param string $where_function
     * @param array $joinTable
     * @return \Closure
     */
    protected static function baseGetListWithPage($perPage = '', $page = '', array $where = [], array $status = [], array $columns = ['*'], $where_function = 'where', array $joinTable = [] ){
        return function () use($perPage, $page, $where, $status, $joinTable, $columns, $where_function) {
            $self = static::setModel(static::getModel());

            if (!empty($where)) {
                event(new HandleModelEvent($self, $where, $where_function));
            }

            if (!empty($query_flag_field = self::query_flag_field()))
                $self = $self->where($query_flag_field[0], $query_flag_field[1]);

            if (!empty($status))
                $self = $self->whereIn($status[0],$status[1]);

            if (!empty($order_by_arr = self::$order_by)){
                foreach ($order_by_arr as $k => $v){
                    $self = $self->orderBy($k, $v);
                }
                self::$order_by = [];
            }

            if (!empty($group_by_arr = self::$group_by)){
                foreach ($group_by_arr as $k => $v){
                    $self = $self->groupBy($k, $v);
                }
                self::$group_by = [];
            }

            if (!empty($joinTable))
                $self = $self->with($joinTable);

            return $self->paginate($perPage, self::handle_columns($columns), 'page', $page);
        };
    }



}