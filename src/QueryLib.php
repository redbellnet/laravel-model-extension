<?php namespace RedBellNet\ModelExtension;

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
        $query_flag_field = config('system.query_flag_field');

        if (self::$is_open_query_flag_field && $query_flag_field && $query_flag_field_value = call_user_func(config('system.query_flag_field'))) {
            return [$query_flag_field,$query_flag_field_value];
        }
        self::$is_open_query_flag_field = true;
        return [];
    }

    protected static function handle_get_by_id_data_to_redis($data){
        if (!empty($data)){
            if (!$data->is_from_cache){
                $create_keys = self::getData(self::query_flag_field_for_redis_key(static::class.'_create_fields'), 'smembers');

                if (!empty($create_keys)){
                    foreach ($create_keys as $k=>$v){
                        self::setData(self::query_flag_field_for_redis_key(static::class.'_field_'.$v.'_'.$data->$v), $data->id, 'sadd');
                    }
                }
            }
        }
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
    protected static function checkExist($field, $value, array $status = [], array $other_where = [], array $return_field = []){
        return function () use($field, $value, $status, $other_where, $return_field){
            $self = static::where([$field => $value]);

            if (!empty($other_where)){
                foreach ($other_where as $k => $v){
                    if (is_array($v)){
                        $self = $self->{$v[0]}($k, $v[1]);
                    } else {
                        $self = $self->where($k, $v);
                    }
                }
            }

            if (!empty($query_flag_field = self::query_flag_field()))
                $self = $self->where($query_flag_field[0],$query_flag_field[1]);

            if (!empty($status))
                $self = $self->whereIn($status[0],$status[1]);

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
                if (is_string($where_function))
                    $self = self::handle_base_where_function($self, $where, $where_function);

                if (is_array($where_function)) {
                    $self = self::handle_base_where_function($self, $where, 'where');
                    $self = self::handle_array_where_function($self, $where_function);
                }
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

            if (!empty($joinTable))
                $self = $self->with($joinTable);

            if (is_array($id))
                return $self->get(self::handle_columns($columns));
            else
                return $self->first(self::handle_columns($columns));
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
                if (is_string($where_function))
                    $self = self::handle_base_where_function($self, $where, $where_function);

                if (is_array($where_function)) {
                    $self = self::handle_base_where_function($self, $where, 'where');
                    $self = self::handle_array_where_function($self, $where_function);
                }
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