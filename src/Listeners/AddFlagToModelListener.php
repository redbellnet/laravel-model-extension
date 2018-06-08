<?php

namespace RedBellNet\ModelExtension\Listeners;

use RedBellNet\ModelExtension\Event\HandleModelEvent;
class AddFlagToModelListener
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


    }

}
