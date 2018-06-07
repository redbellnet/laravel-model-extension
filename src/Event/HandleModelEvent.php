<?php

namespace RedBellNet\ModelExtension\Event;


class HandleModelEvent
{

    public $model;

    public $where;

    public $where_function;

    /**
     * Create a new event instance.
     *
     * @param $model
     * @param $where
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
     * @param string $where_function
     */
    public function __construct($model, $where, $where_function = 'where')
    {
        $this->model = $model;
        $this->where = $where;
        $this->where_function = $where_function;
    }
}
