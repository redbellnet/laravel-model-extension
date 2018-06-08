<?php namespace RedBellNet\ModelExtension;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use RedBellNet\ModelExtension\Event\HandleModelEvent;

trait Util
{
    /**
     * @var bool
     */
    protected  $is_open_query_flag_field = true;

    /**
     * @var string
     */
    public $select = ['*'];

    public $redis_key = [];

    protected $model;
    protected $builder;


    /**
     * @param mixed $parameters_for_redis_key
     */
    public function setParametersForRedisKey($parameters_for_redis_key)
    {
        $this->redis_key['parameters'] = $parameters_for_redis_key;
    }


    /**
     * @param mixed $redis_key
     */
    public function setRedisKey($redis_key)
    {
//        if (empty($redis_key)){
//            $redis_key = get_class($this->model);
//        } else {
//            $redis_key = join("_",[get_class($this->model), $redis_key]);
//        }

        $this->redis_key = $redis_key;
    }

    /**
     * @return mixed
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param mixed $builder
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }


    /**
     * @Name query_flag_field_for_redis_key
     * @Created by yuxuewen.
     * @Description 若有查询标志字段，则加入相应的redis的key中
     * @param $redis_key
     * @return string
     */
    public function query_flag_field_for_redis_key(){
        if (!empty($query_flag_field = $this->query_flag_field())){

            $this->redis_key['query_flag_field'] = join("_", $query_flag_field);
        }


    }


    public function field_for_redis_key(){
        if (!isset($this->redis_key['field'])){
            $this->redis_key['field'] = '';
        }

        if (!empty($new_field = func_get_args())){

            $old_field = json_decode($this->redis_key['field']);
            if (!empty($old_field)){
                $new_field = json_encode(array_merge($old_field, $new_field));
            } else {
                $new_field = json_encode($new_field);
            }
            $this->redis_key['field'] = $new_field;
        }



    }



    /**
     * @Name query_flag_field
     * @Created by yuxuewen.
     * @Description
     * @return array
     */
    protected function query_flag_field(){
        $query_flag_field = config('modelExtension.query_flag_field');

        if (empty($query_flag_field)) return [];

        if (!function_exists($query_flag_field)){
            abort(422, $query_flag_field.' not found, you can create it in helper.');
        }

        if ($this->is_open_query_flag_field
                && $query_flag_field
                && $query_flag_field_value = call_user_func($query_flag_field)
            ) {
            return [$query_flag_field,$query_flag_field_value];
        }

        $this->is_open_query_flag_field = true;
        return [];
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
     * @Name select
     * @Created by yuxuewen.
     * @Description 需要查询的字段
     * @return $this
     */
    public function select(){
        $this->select = !empty(func_get_args())?func_get_args():$this->select;

        return $this;
    }

    /**
     * @Name handle_columns
     * @Created by yuxuewen.
     * @Description 空数组或者是*的查询字段，自动读取model里面定义的字段
     * @param array $columns
     * @return array
     */
    protected function handle_columns(array $columns){
        if ($this->select[0] != '*'){
            return $this->select;
        }

        if (empty($columns) || $columns[0] == '*'){
            return \Schema::getColumnListing($this->model->getTable());
        } else {
            return $columns;
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
     * @Name handle_base_where_function
     * @Created by yuxuewen.
     * @Description 处理where条件
     * @param Model $model
     * @param array $where
     *          使用方式：
     *                  只有一个条件  [ 条件 ]
     *                  多个条件     [ [ 条件1 ], [ 条件2 ] ]
     *          支持的条件方式：
     *                  1、 [field, value]
     *                              demo                [ 'id', 1 ]
     *                              对应laravel语句     where('id', 1)
     *                  2、 [field , condition, value]
     *                              demo                [ 'id', '!=', 1 ]
     *                              对应的laravel语句    where('id', '!=', 1)
     *                  3、 [field => value]
     *                              demo                [ 'id' => 1]
     *                              对应laravel语句     where('id', 1)
     *                  4、 [field => [condition, value] ]
     *                              demo                [ id => ['!=', 1] ]
     *                              对应laravel语句     where('id', '!=', 1)
     *                  5、[condition => [field, args...] ]  此处args为laravel可支持的参数个数
     *                              demo                [ 'whereIn' => ['id', [1,2,3]] ]
     *                              对应laravel语句     whereIn('id', [1, 2, 3])
     *                              demo2               [ 'whereColumn' => ['updated_at', '>', 'created_at'] ]
     *                              对应laravel语句     whereColumn('updated_at', '>', 'created_at')
     *
     * @param string $where_function
     * @return Model
     */
    protected static function handle_base_where_function(Builder $model, array $where, $where_function = 'where'){
        if (empty($where)) return $model;

        event(new HandleModelEvent($model, $where, $where_function));

        $keys = array_keys($where);
        if ( $keys === array_keys($keys)){
            $model = $model->{$where_function}(...$where);
        } else {
            foreach ($where as $k => $v) {
                if ((new \ReflectionClass(Builder::class))->hasMethod($k)
                    && (new \ReflectionMethod(Builder::class, $k))->isPublic()
                    || (new \ReflectionClass(QueryBuilder::class))->hasMethod($k)
                    && (new \ReflectionMethod(QueryBuilder::class, $k))->isPublic()){
                    $where_function = $k;
                } else {
                    array_unshift($v, $k);
                }
                $model = $model->{$where_function}(...$v);
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