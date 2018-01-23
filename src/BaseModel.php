<?php namespace RedBellNet\ModelExtension;

use RedBellNet\ModelExtension\QueryLib;
use Illuminate\Support\Facades\DB;

trait BaseModel
{

    use QueryLib;

    public static function is_success(){
        echo "success";
    }

    /**
     * @Name checkIdExist
     * @Created by yuxuewen.
     * @Description 通过ID判断数据是否存在
     * @param $id
     * @param array $status
     * @return bool
     */
    public static function checkIdExist($id, $status = 'normal_status_arr'){
        $redis_key = 'checkIdExist_id_'.$id;
        $redis_key = self::query_flag_field_for_redis_key($redis_key);

        $result = static::redis($redis_key,static::checkExist('id',$id,self::is_set_status($status)));
        if ($result->isEmpty()) return false;

        return true;
    }

    /**
     * @Name checkIdExistSimple
     * @Created by yuxuewen.
     * @Description 通过ID判断数据是否存在简单版，不查询status字段
     * @param $id
     * @return bool
     */
    public static function checkIdExistSimple($id){
        return self::checkIdExist($id,'');
    }

    /**
     * @Name checkNameExist
     * @Created by yuxuewen.
     * @Description 判断名称是否存在
     * @param $name
     * @param string $status
     * @return bool
     */
    public static function checkNameExist($name, $status = 'normal_status_arr'){
        $redis_key = 'checkIdExist_name_'.$name;
        $redis_key = self::query_flag_field_for_redis_key($redis_key);

        $result = static::redis($redis_key,static::checkExist('name',$name,self::is_set_status($status),[],['id']));

        if ($result->isEmpty()) return false;

        return $result->first()->id;
    }

    /**
     * @Name checkNameExistSimple
     * @Created by yuxuewen.
     * @Description 判断名称是否存在简单版，不查询status字段
     * @param $name
     * @return bool
     */
    public static function checkNameExistSimple($name){
        return self::checkNameExist($name, '');
    }

    /**
     * @Name checkFieldExist
     * @Created by yuxuewen.
     * @Description 检查指定的某个字段是否在记录中存在
     * @param $field
     * @param $value
     * @param string $status
     * @param array $other_where
     * @param array $return_field
     * @return bool
     */
    public static function checkFieldExist($field, $value, $status = 'normal_status_arr', $other_where = [], $return_field = []){
        $redis_key = 'checkFieldExist_'.$field.'_'.$value;
        $redis_key = self::query_flag_field_for_redis_key($redis_key);

        $result = static::redis($redis_key,static::checkExist($field, $value, self::is_set_status($status), $other_where, array_merge(['id'],$return_field)));

        if ($result->isEmpty()) return false;

        return $result->first()->id;
    }

    /**
     * @Name checkFieldExistSimple
     * @Created by yuxuewen.
     * @Description 检查指定的某个字段是否在记录中存在简单版，不查询status字段
     * @param $field
     * @param $value
     * @return bool
     */
    public static function checkFieldExistSimple($field, $value){
        return self::checkFieldExist($field, $value, '');
    }

    /**
     * @Name add
     * @Created by yuxuewen.
     * @Description                    添加数据
     * @param array $value             添加的数据
     * @param bool $unset_empty_keys   剔除数组中的空值
     * @return static
     */
    public static function add(array $value, $unset_empty_keys = true){
        if ($unset_empty_keys) $value = unset_empty_keys($value);

        $query_flag_field = self::query_flag_field();
        if (!empty($query_flag_field))
            $value = array_merge($value,[$query_flag_field[0]=>$query_flag_field[1]]);

        if(count($value) == count($value,true)){
            $result = static::create($value);
        }else{
            $result = static::insert($value);
        }

        if ($result){
            $redis_key = static::class.'_lists';
            $redis_key = self::query_flag_field_for_redis_key($redis_key);

            self::RedisFlushByKey($redis_key.'*');
            return $result;
        } else {
            return false;
        }
    }

    /**
     * @Name put
     * @description                    修改数据
     * @param int $id                   条件ID
     * @param array $value              更新的数据
     * @param bool $unset_empty_keys    剔除数组中的空值
     * @return array
     */
    public static function put($id = 0, array $value, $unset_empty_keys = true){
        if ($unset_empty_keys) $value = unset_empty_keys($value);

        if ($result = self::basePut(['id'=>$id], $value)){
            self::RedisFlushByKey(self::query_flag_field_for_redis_key(static::class.'_lists').'*');
            self::RedisFlushByKey(self::query_flag_field_for_redis_key(static::class.'_id_'.$id).'*');
            self::RedisFlushByKey(self::query_flag_field_for_redis_key(static::class.'_field_value').'*');
            return $result;
        } else {
            return false;
        }
    }


    /**
     * @Name putByField
     * @Created by yuxuewen.
     * @Description
     * @param array $field_where 更新的where条件
     * @param array $field_value 需要更新的字段，该字段的值
     * @param string $status
     * @param bool $unset_empty_keys
     * @return bool
     */
    public static function putByField(array $field_where = [], array $field_value = [], $status = 'normal_status_arr', $unset_empty_keys = true ){
        if ($unset_empty_keys) $field_value = unset_empty_keys($field_value);

        $id_arr = [];
        $put_field = config('modelExtension.put_field');
        $model = last(explode("\\",static::class));

        if (!empty($put_field)){
            foreach ($put_field as $k=>$v){
                if ($k == $model){
                    self::setData(self::query_flag_field_for_redis_key(static::class.'_create_fields'), $v, 'sadd');
                    break;
                }
            }
        }

        foreach ($field_where as $k=>$v ){
            $keys = self::getKeys(self::query_flag_field_for_redis_key(static::class.'_field_'.$k.'*'));
            if (empty($keys)){
                //clear all of model's id record
                self::RedisFlushByKey(self::query_flag_field_for_redis_key(static::class.'_id_*'));

                self::setData(self::query_flag_field_for_redis_key(static::class.'_create_fields'), $k, 'sadd');

            } else {
                $ids = self::getData(self::query_flag_field_for_redis_key(static::class.'_field_'.$k.'_'.$v), 'smembers');
                if (empty($id_arr))
                    $id_arr = $ids;
                else
                    $id_arr = array_intersect($id_arr, $ids);
            }
        }






        if (self::basePut($field_where, $field_value, self::is_set_status($status))){

            $relation_set = self::getData(self::query_flag_field_for_redis_key($model.'_join_table_with_model_relation'), 'smembers');
            if (!empty($relation_set)){
                foreach ($relation_set as $k=>$v){
                    // clear join table id record
                    self::RedisFlushByKey(self::query_flag_field_for_redis_key($v.'_id_'.$v.'*'));
                    // clear join table list
                    self::RedisFlushByKey(self::query_flag_field_for_redis_key($v.'_lists').'*');
                    self::RedisFlushByKey(self::query_flag_field_for_redis_key($v.'_field_value').'*');
                }
            }

            // clear self ids record
            if (!empty($id_arr)){
                foreach ($id_arr as $v){
                    self::RedisFlushByKey(self::query_flag_field_for_redis_key(static::class.'_id_'.$v.'*'));
                }
            }

            // clear self list
            self::RedisFlushByKey(self::query_flag_field_for_redis_key(static::class.'_lists').'*');
            self::RedisFlushByKey(self::query_flag_field_for_redis_key(static::class.'_field_value').'*');
            return true;
        } else {
            return false;
        }
    }


    /**
     * @author  dyl
     * @description 根据id修改记录,不包含shop_id(待完善优化!!!)
     * @param int $id                   id
     * @param array $value              要修改的数据
     * @param bool $unset_empty_keys    是否去除空值
     * @return bool|string
     */
    public static function putByIdSimple($id = 0, array $value, $unset_empty_keys = true){
        if ($unset_empty_keys) $value = unset_empty_keys($value);

        if ($result = self::where(['id' => $id])->update($value)){
            self::RedisFlushByKey(static::class.'_lists_*');
            self::RedisFlushByKey(static::class.'_id_'.$id.'*');
            self::RedisFlushByKey(static::class.'_field_value_*');
            return $result;
        } else {
            return false;
        }
    }


    /**
     * @Name del
     * @Created by yuxuewen.
     * @Description 删除数据
     * @param $id
     * @return string
     */
    public static function del($id){
        return self::put($id,['status' => 0],false);
    }

    /**
     * @Name delByField
     * @Created by yuxuewen.
     * @Description 根据指定的where条件来删除数据
     * @param array $field_where
     * @return bool
     */
    public static function delByField(array $field_where){
        return self::putByField($field_where, ['status' => 0]);
    }

    /**
     * @Name getListByShopId
     * @Created by yuxuewen.
     * @Description             获取列表信息
     * @param string $perPage 每页显示多少条数据
     * @param string $page 当前页
     * @param array $filter 筛选条件
     * @param array $columns 显示的数据列
     * @param array $order_by 排序条件
     * @param string $status 状态
     * @param string $where_function
     * @return mixed
     */
    public static function getListByShopId($perPage = '', $page = '', array $filter = [], array $columns = ['*'], $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){

        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_page_'.$perPage.'_'.$page.'_filter_'.json_encode($filter).'_by_field_'.json_encode($columns).'_order_by_'.json_encode($order_by).'_status_'.json_encode($status);

        return self::redis($redis_key,self::baseGetListWithPage($perPage,$page,$filter,self::is_set_status($status), $columns, $where_function));
    }

    /**
     * @Name getList
     * @Created by yuxuewen.
     * @Description getListByShopId 别名
     * @param string $perPage
     * @param string $page
     * @param array $filter
     * @param array $columns
     * @param array $order_by
     * @param string $status
     * @param string $where_function
     * @return mixed
     */
    public static function getList($perPage = '', $page = '', array $filter = array(), array $columns = array('*'), $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){
        return self::getListByShopId($perPage, $page, $filter, $columns, $order_by, $status, $where_function);
    }


    /**
     * @Name getListByFieldAndLikes
     * @Created by yuxuewen.
     * @Description 带查询条件和模糊搜索添加的获取列表功能
     * @param $perPage
     * @param $page
     * @param array $filterWhere
     * @param array $filterLike
     * @param array $columns
     * @param array $order_by
     * @param string $status
     * @param string $where_function
     * @return string
     * @internal param string $orderBy
     */
    public static function getListByFieldAndLikes($perPage, $page, array $filterWhere = [], array $filterLike = [], $columns = array('*'), $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){

        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_page_'.$perPage.'_'.$page.'_filterWhere_'.json_encode($filterWhere).'_filterLike_'.json_encode($filterLike).'_by_field_'.json_encode($columns).'_order_by_'.json_encode($order_by).'_status_'.json_encode($status);

        $where['where'] = function($query) use($filterLike){
            self::arrayChangeToOrWhere($query,$filterLike);
        };

        $where = array_merge($filterWhere,$where);

        self::set_order_by($order_by);


        return self::redis($redis_key,self::baseGetListWithPage($perPage,$page,$where,self::is_set_status($status), $columns, $where_function));
    }

    /**
     * @Name getListByFieldAndLikesJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param $perPage
     * @param $page
     * @param array $joinTable
     * @param array $filterWhere
     * @param array $filterLike
     * @param array $columns
     * @param array $order_by
     * @param string $status
     * @param string $where_function
     * @return mixed
     */
    public static function getListByFieldAndLikesJoinTable($perPage, $page, array $joinTable, array $filterWhere = [], array $filterLike = [], $columns = array('*'), $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){

        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_page_'.$perPage.'_'.$page.'_joinTable_'.json_encode($joinTable).'_filterWhere_'.json_encode($filterWhere).'_filterLike_'.json_encode($filterLike).'_by_field_'.json_encode($columns).'_order_by_'.json_encode($order_by).'_status_'.json_encode($status);

        $where['where'] = function($query) use($filterLike){
            self::arrayChangeToOrWhere($query,$filterLike);
        };

        $where = array_merge($filterWhere,$where);

        self::set_order_by($order_by);


        return self::redis($redis_key,self::baseGetListWithPage($perPage,$page,$where,self::is_set_status($status),$columns, $where_function, $joinTable));
    }

    /**
     * @Name getListByShopIdJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param string $perPage 每页显示多少条数据
     * @param string $page 当前页
     * @param array $joinTable 关联表内容，格式[关联关系,匿名函数处理条件类语句]
     *              如：  [
     *                        'memberLevel',
     *                        function($query){
     *                            $query->select('id','name');
     *                        }
     *                    ]
     * @param array $columns
     * @param array $filter
     * @param array $order_by
     * @param string $status
     * @param string $where_function
     * @return mixed
     * @internal param string $orderBy
     */
    public static function getListByShopIdJoinTable($perPage, $page, array $joinTable, array $columns = array('*'), array $filter = array(), $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_page_'.$perPage.'_'.$page.'_joinTable_'.json_encode($joinTable).'_filter_'.json_encode($filter).'_by_field_'.json_encode($columns).'_order_by_'.json_encode($order_by).'_status_'.json_encode($status);

        self::set_order_by($order_by);

        return self::redis($redis_key,self::baseGetListWithPage($perPage,$page,$filter,self::is_set_status($status),$columns, $where_function, $joinTable));
    }

    public static function getListJoinTable($perPage, $page, array $joinTable, array $filter = array(), array $columns = array('*'), $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){
        return self::getListByShopIdJoinTable($perPage, $page, $joinTable, $columns, $filter, $order_by, $status, $where_function);
    }

    /**
     * @Name getListOrLike
     * @param string $perPage 每页显示多少条数据
     * @param string $page 当前页
     * @param array $filter 筛选条件
     * @param array $columns 显示的数据列
     * @param array $order_by
     * @param string $status 状态
     * @param string $where_function
     * @return mixed
     */
    public static function getListOrLike($perPage = '', $page = '', array $filter = [], array $columns = array('*'), $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){

        return self::getListByFieldAndLikes($perPage, $page, [], $filter, $columns, $order_by, $status, $where_function);
    }

    /**
     * @Name getListOrLikeJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param string $perPage
     * @param string $page
     * @param array $joinTable
     * @param array $filter
     * @param array $columns
     * @param array $order_by
     * @param string $status
     * @param string $where_function
     * @return mixed
     */
    public static function getListOrLikeJoinTable($perPage = '', $page = '', array $joinTable, array $filter = [], array $columns = array('*'), $order_by = [], $status = 'normal_status_arr', $where_function = 'where'){

        return self::getListByFieldAndLikesJoinTable($perPage, $page, $joinTable, [], $filter, $columns, $order_by, $status, $where_function);
    }

    /**
     * @Name getAllListByShopId
     * @Created by yuxuewen.
     * @Description                 获取所有列表的内容
     * @param array $columns 显示的数据列
     * @param string $order_by 排序条件
     * @param array $field_value 筛选条件
     * @param mixed $status 状态
     * @return mixed
     */
    public static function getAllListByShopId(array $columns = ['*'], $order_by = [], array $field_value = [], $status = 'use_status_arr', $where_function = 'where'){
        return self::getByField($field_value, $columns, $status, $order_by, $where_function);
    }

    public static function getAllListByShopIdJoinTable(array $columns = ['*'], array $joinTable, $order_by = [], array $field_value = [], $status = 'use_status_arr', $where_function = 'where'){
        return self::getByFieldJoinTable($field_value, $joinTable, $columns, $status, $where_function);
    }

    /**
     * @Name getById
     * @Created by yuxuewen
     * @Description             主键获取内容
     * @param int $id            主键
     * @param array $columns     显示的数据列
     * @param string $status     状态
     * @return mixed
     */
    public static function getById($id = 0, array $columns = array('*'), $status = 'normal_status_arr'){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_id_'.$id);
        $redis_key .= '_by_field_'.json_encode($columns);
        $redis_key .= '_static_'.$status;

        $data = self::redis($redis_key, static::baseGetByID($id,self::is_set_status($status)));
        self::handle_get_by_id_data_to_redis($data);
        return $data;
    }

    public static function getByIdJoinTable($id = 0, array $joinTable,  array $columns = array('*'), $status = 'normal_status_arr'){

        $redis_key = self::query_flag_field_for_redis_key(static::class.'_id_'.$id);
        $redis_key .= '_by_field_'.json_encode($columns);
        $redis_key .= '_static_'.$status;
        $redis_key .= '_joinTable_'.json_encode($joinTable);

        $data = self::redis($redis_key,static::baseGetByID($id,self::is_set_status($status),[],$columns,$joinTable));
        self::handle_get_by_id_data_to_redis($data);
        return $data;
    }

    /**
     * @Name getByIds
     * @Created by yuxuewen.
     * @Description
     * @param array $ids
     * @param array $columns
     * @param string $status
     * @param array $field_value
     * @return string
     */
    public static function getByIds(array $ids = [], array $columns = array('*'), $status = 'normal_status_arr', array $field_value = []){
        foreach ($ids as $v) if (!is_numeric($v)) self::forbiddenResponse(['非法参数']);

        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_ids_'.json_encode($ids).'_where_'.json_encode($field_value).'_status_'.json_encode($status).'_by_field_'.json_encode($columns);

        return self::redis($redis_key, static::baseGetByID($ids,self::is_set_status($status),$field_value,$columns));
    }

    /**
     * @Name getByIdsSimple
     * @Created by yuxuewen.
     * @Description 简单版查询多个ID的数据
     * @param array $ids
     * @param array $columns
     * @param array $field_value
     * @return string
     */
    public static function getByIdsSimple(array $ids = [], array $columns = array('*'), array $field_value = []){
        foreach ($ids as $v) if (!is_numeric($v)) self::forbiddenResponse(['非法参数']);
        return self::getByIds($ids,$columns,'',$field_value);
    }

    /**
     * @Name getByField
     * @Created by yuxuewen.
     * @Description               根据某个字段查询值
     * @param array $field_where
     * @param array $columns 显示的数据列
     * @param string $status 状态
     * @param array $order_by
     * @param string $where_function
     * @return mixed
     * @internal param array $field_value 条件列
     */
    public static function getByField(array $field_where, array $columns = array('*'), $status = 'normal_status_arr',array $order_by = [], $where_function='where', $query_function=''){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_where_'.json_encode($field_where).'_status_'.json_encode($status).'_by_field_'.json_encode($columns).'order_by'.json_encode($order_by);

        self::set_order_by($order_by);

        return self::redis($redis_key,self::baseGetByField($field_where,self::is_set_status($status),$columns,$where_function, $query_function));
    }

    /**
     * @Name getByFieldJoinTable
     * @param array $field_where
     * @param array $joinTable 关联的数据表
     * @param array $columns 显示的数据列
     * @param string $status 状态
     * @param array $order_by
     * @param string $where_function
     * @param string $query_function
     * @return mixed
     * @internal param array $field_value 条件
     */
    public static function getByFieldJoinTable(array $field_where = [], array $joinTable, array $columns = array('*'), $status = 'normal_status_arr', array $order_by = [], $where_function='where', $query_function=''){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_joinTable_'.json_encode($joinTable).'_where_'.json_encode($field_where).'_status_'.json_encode($status).'_by_field_'.json_encode($columns).'order_by'.json_encode($order_by);

        self::set_order_by($order_by);

        return self::redis($redis_key,self::baseGetByFieldJoinTable($field_where, $joinTable, self::is_set_status($status), $columns, $where_function, $query_function));
    }

    /**
     * @Name getByFieldSimple
     * @Created by yuxuewen.
     * @Description 简单版 根据某个字段查询值
     * @param array $field_where
     * @param array $columns
     * @param array|string $orderBy
     * @return mixed
     * @internal param array $field_value
     */
    public static function getByFieldSimple(array $field_where, array $columns = array('*'), $order_by = []){
        return self::getByField($field_where, $columns, '', $order_by);
    }

    /**
     * @Name getInField
     * @Created by yuxuewen.
     * @Description
     * @param array $field_whereIn
     * @param array $columns
     * @param array $field_where
     * @param string $status
     * @param array $order_by
     * @param string $query_function
     * @return mixed
     */
    public static function getInField(array $field_whereIn, array $columns = array('*'), array $field_where = [], $status = 'normal_status_arr', array $order_by = [], $query_function='', $is_open_query_flag_field=true){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_whereIn_'.json_encode($field_whereIn).'_where_'.json_encode($field_where).
                    '_status_'.json_encode($status).'_by_field_'.json_encode($columns).'order_by'.json_encode($order_by);

        self::set_order_by($order_by);

        self::set_is_open_query_flag_field($is_open_query_flag_field);

        if (!empty($field_where))
            return self::redis($redis_key,self::baseGetByField($field_where,self::is_set_status($status),$columns,['whereIn'=>$field_whereIn], $query_function));
        else
            return self::redis($redis_key,self::baseGetByField($field_whereIn,self::is_set_status($status),$columns,'whereIn', $query_function));
    }

    /**
     * @Name getInFieldJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param array $field_whereIn
     * @param array $joinTable
     * @param array $columns
     * @param array $field_where
     * @param string $status
     * @param array $order_by
     * @param string $query_function
     * @param bool $is_open_query_flag_field
     * @return mixed
     */
    public static function getInFieldJoinTable(array $field_whereIn, array $joinTable, array $columns = array('*'),
                                               array $field_where = [], $status = 'normal_status_arr', array $order_by = [], $query_function='', $is_open_query_flag_field=true){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_joinTable_'.json_encode($joinTable).'_whereIn_'.json_encode($field_whereIn).
                    '_where_'.json_encode($field_where).'_status_'.json_encode($status).'_by_field_'.
                    json_encode($columns).'order_by'.json_encode($order_by);

        self::set_order_by($order_by);

        self::set_is_open_query_flag_field($is_open_query_flag_field);

        if (!empty($field_where))
            return self::redis($redis_key,self::baseGetByFieldJoinTable($field_where, $joinTable, self::is_set_status($status), $columns, ['whereIn'=>$field_whereIn], $query_function));
        else
            return self::redis($redis_key,self::baseGetByFieldJoinTable($field_whereIn, $joinTable, self::is_set_status($status), $columns, 'whereIn', $query_function));
    }

    /**
     * @Name getInFieldWithPageJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param string $perPage
     * @param string $page
     * @param array $field_whereIn
     * @param array $joinTable
     * @param array $columns
     * @param array $field_where
     * @param string $status
     * @param array $order_by
     * @param bool $is_open_query_flag_field
     * @return mixed
     */
    public static function getInFieldWithPageJoinTable($perPage = '', $page = '', array $field_whereIn, array $joinTable, array $columns = array('*'),
                                                       array $field_where = [], $status = 'normal_status_arr', array $order_by = [], $is_open_query_flag_field = true){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_page_'.$perPage.'_'.$page.'_joinTable_'.json_encode($joinTable).'_whereIn_'.json_encode($field_whereIn).
            '_where_'.json_encode($field_where).'_status_'.json_encode($status).'_by_field_'.
            json_encode($columns).'order_by'.json_encode($order_by);

        self::set_order_by($order_by);

        self::set_is_open_query_flag_field($is_open_query_flag_field);
        if (!empty($field_where))
            return self::redis($redis_key,self::baseGetByFieldWithPageJoinTable($perPage, $page, $field_where, $joinTable, self::is_set_status($status), $columns, ['whereIn'=>$field_whereIn]));
        else
            return self::redis($redis_key,self::baseGetByFieldWithPageJoinTable($perPage, $page, $field_whereIn, $joinTable, self::is_set_status($status), $columns, 'whereIn'));
    }

    /**
     * @Name getInFieldSimple
     * @Created by yuxuewen.
     * @Description
     * @param array $field_whereIn
     * @param array $columns
     * @param array $field_where
     * @param array $order_by
     * @return mixed
     */
    public static function getInFieldSimple(array $field_whereIn, array $columns = array('*'), array $field_where = [], $order_by = [], $query_function=''){
        return self::getInField($field_whereIn, $columns, $field_where, '', $order_by, $query_function, false);
    }

    /**
     * @Name getInFieldJoinTableSimple
     * @Created by yuxuewen.
     * @Description
     * @param array $field_whereIn
     * @param array $joinTable
     * @param array $columns
     * @param array $field_where
     * @param array $order_by
     * @param string $query_function
     * @return mixed
     */
    public static function getInFieldJoinTableSimple(array $field_whereIn, array $joinTable, array $columns = array('*'), array $field_where = [], $order_by = [], $query_function=''){
        return self::getInFieldJoinTable($field_whereIn, $joinTable, $columns, $field_where, '', $order_by, $query_function, false);
    }

    /**
     * @Name getOneByField
     * @Created by yuxuewen.
     * @Description 获得符合条件的第一条信息
     * @param array $field_where
     * @param array $columns
     * @param string $status
     * @param array $order_by
     * @return mixed
     */
    public static function getOneByField(array $field_where, array $columns = array('*'), $status = 'normal_status_arr', $order_by = []){
        return self::getByField($field_where,$columns,$status,$order_by,'where','first');
    }

    /**
     * @Name getOneByFieldJoinTable
     * @Created by yuxuewen.
     * @Description
     * @param array $field_where
     * @param array $joinTable
     * @param array $columns
     * @param string $status
     * @param array $order_by
     * @return mixed
     */
    public static function getOneByFieldJoinTable(array $field_where, array $joinTable, array $columns = array('*'), $status = 'normal_status_arr', $order_by = []){
        return self::getByFieldJoinTable($field_where,$joinTable,$columns,$status,$order_by,'where','first');
    }

    /**
     * @Name getByFieldOrLike
     * @Created by yuxuewen.
     * @Description
     * @param array $like_where
     * @param array $columns
     * @param array $other_where
     * @param string $status
     * @param array $orderBy
     * @return mixed
     */
    public static function getByFieldOrLike(array $like_where, array $columns = array('*'), array $other_where = [], $status = 'normal_status_arr', $order_by = []){
        $where ['where'] = function($query) use($like_where){
            self::arrayChangeToOrWhere($query,$like_where);
        };
        $where = array_merge($other_where,$where);
        return self::getByField($where,$columns,$status,$order_by,'where','first');
    }


    /**
     * @Name getOne
     * @Created by yuxuewen.
     * @Description
     * @param array $columns
     * @param string $status
     * @param array $order_by
     * @return mixed
     * @internal param string $orderBy
     */
    public static function getOne(array $columns = array('*'), $order_by = '', $status = 'normal_status_arr'){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_one_status_'.json_encode($status).'_by_field_'.json_encode($columns).'order_by'.json_encode($order_by);
        if (!empty($order_by))
            $order_by = ['id'=>$order_by];

        self::set_order_by($order_by);

        return self::redis($redis_key,self::baseGet($columns, self::is_set_status($status), 'where', 'first'));
    }

    /**
     * @Name getAll
     * @Created by yuxuewen.
     * @Description
     * @param array $columns
     * @param string $status
     * @param string $order_by
     * @return mixed
     * @internal param string $orderBy
     */
    public static function getAll(array $columns = array('*'), $status = 'normal_status_arr', $order_by = ''){
        $redis_key = self::query_flag_field_for_redis_key(static::class.'_lists');
        $redis_key .= '_all_status_'.json_encode($status).'_by_field_'.json_encode($columns).'order_by'.json_encode($order_by);

        if (!empty($order_by))
            $order_by = ['id'=>$order_by];

        self::set_order_by($order_by);

        return self::redis($redis_key,self::baseGet($columns, self::is_set_status($status)));
    }

    /**
     * @Name getSecletAdress
     * @Created by yuxuewen.
     * @Description         获取地址信息
     * @param $province_id   省ID
     * @param $city_id       城市ID
     * @param $district_id   街道ID
     * @return array
     */
    public static function getSelectAddress($province_id, $city_id = null, $district_id = null){
        $address = array();

        //根据省份id,获取省份对应的所有城市的值
        $address['cityModel'] =  static::redis('get_city_by_province_id_'.$province_id, function() use ($province_id){
            return Province::find($province_id)->city;
        });

        //根据城市id,获取城市对应的所有区域的值
        $city_id = !empty($city_id) ? $city_id : $address['cityModel'][0]->id;
        $address['districtModel'] =  static::redis('get_district_by_city_id_'.$city_id, function() use ($city_id){
            return City::find($city_id)->district;
        });

        //根据区域id,获取区域对应的所有街道的值(方法二:在后台返回-速度快一些;也可在页面用jq加载-速度较慢)
        $district_id = !empty($district_id) ? $district_id : $address['districtModel'][0]->id;
        $address['roadModel'] =  static::redis('get_road_by_district_id_'.$district_id, function() use ($district_id){
            return District::find($district_id)->road;
        });

        return $address;
    }



    /**
     * @description                 批量修改数据
     * @param array $multipleData   需要批量修改的数据
     * @return bool
     */
    public static function updateBatch($multipleData = array()){
        /*
        $multipleData = array(
           array(
              'title' => 'My title' ,
              'name' => 'My Name 2' ,
              'date' => 'My date 2'
           ),
           array(
              'title' => 'Another title' ,
              'name' => 'Another Name 2' ,
              'date' => 'Another date 2'
           )
        )
        */
        if( !empty($multipleData) ) {

            // column or fields to update
            $updateColumn = array_keys($multipleData[0]);
            $referenceColumn = $updateColumn[0];
            unset($updateColumn[0]);
            $whereIn = "";

            $table_name_array = explode('\\', static::class);   //后期静态绑定数据表名称,以'\'切割成数组
            $table_name = strtolower(end($table_name_array));    //获取数组最后一个元素的值,并转为小写

            $q = "UPDATE ".$table_name." SET ";
            foreach ( $updateColumn as $uColumn ) {
                $q .=  $uColumn." = CASE ";

                foreach( $multipleData as $data ) {
                    $q .= "WHEN ".$referenceColumn." = ".$data[$referenceColumn]." THEN '".$data[$uColumn]."' ";
                }
                $q .= "ELSE ".$uColumn." END, ";
            }
            foreach( $multipleData as $data ) {
                $whereIn .= "'".$data[$referenceColumn]."', ";
            }
            $q = rtrim($q, ", ")." WHERE ".$referenceColumn." IN (".  rtrim($whereIn, ', ').")";

            // Update
            return DB::update(DB::raw($q));

        } else {
            return false;
        }
    }
}