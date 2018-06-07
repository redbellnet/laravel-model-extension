<?php

namespace RedBellNet\ModelExtension\Listeners;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use RedBellNet\ModelExtension\Event\HandleModelEvent;
class AddWhereToModelListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }


    /**
     * @Name handle
     * @Created by yuxuewen.
     * @Description 把where条件加入到model中
     * @param HandleModelEvent $event
     * @return array
     */
    public function handle(HandleModelEvent $event)
    {
        $keys = array_keys($event->where);
        if ( $keys === array_keys($keys)){
            $event->model->{$event->where_function}(...$event->where);
        } else {
            foreach ($event->where as $k => $v) {
                if ((new \ReflectionClass(Builder::class))->hasMethod($k)
                    && (new \ReflectionMethod(Builder::class, $k))->isPublic()
                    || (new \ReflectionClass(QueryBuilder::class))->hasMethod($k)
                    && (new \ReflectionMethod(QueryBuilder::class, $k))->isPublic()){
                    $where_function = $k;
                } else {
                    array_unshift($v, $k);
                }

                if (is_array($v[0])){
                    foreach ($v as $v_v){
                        $event->model->{$where_function}(...$v_v);
                    }
                } else {
                    $event->model->{$where_function}(...$v);
                }

            }
        }

    }

}
