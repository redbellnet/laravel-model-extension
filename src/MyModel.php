<?php

namespace RedBellNet\ModelExtension;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MyModel
 * @package RedBellNet\ModelExtension
 * @author yuxuewen
 * @E-mail 8586826@qq.com
 * @method getById
 * @see BaseModel::getById()
 */
class MyModel extends Model
{
    protected $model;

    protected $builder;

    protected $instance_base_model = '';

    /**
     * @var
     */
    public $parameters_for_redis_key;


    /**
     * @Name instanceBaseModel
     * @Created by yuxuewen.
     * @Description 工厂实例化baseModel
     * @return BaseModel
     */
    protected function instanceBaseModel(){
        if (empty($this->instance_base_model)){
            $baseModel = new BaseModel();
            $baseModel->setModel($this);
            $this->instance_base_model = $baseModel;
        }

        return $this->instance_base_model;
    }

    /**
     * @Name callBaseModelMethod
     * @Created by yuxuewen.
     * @Description 调用baseModel或者builder的方法
     * @param $method
     * @param $parameters
     * @param null $builder
     * @return mixed
     */
    protected function callBaseModelMethod($method, $parameters){


    }


    /**
     * @Name __call
     * @Created by yuxuewen.
     * @Description 处理非静态方法，创建baseModel实例和builder实例
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {

        $base_mode = new \ReflectionClass(BaseModel::class);
        $this->instance_base_model = $this->instanceBaseModel();
        $this->instance_base_model->field_for_redis_key($parameters);

        if ($base_mode->hasMethod($method)){
//            dump('my_model__call__base_model_'.$method);
            if (!$this->builder) $this->builder = $this->newQuery();

            $this->instance_base_model->setBuilder($this->builder);


            return $this->instance_base_model->$method(...$parameters);
        }

        if (empty($this->builder))
        {
//            dump('my_model__call__empty_builder_'.$method);
            $this->builder = $this->newQuery()->$method(...$parameters);
            return $this;
        } else {
//            dump('my_model__call_builder_'.$method);
            $this->builder = $this->builder->$method(...$parameters);
//            dump('my_model__call_builder_class_'.get_class($this->builder));
            if (get_class($this->builder) == Builder::class)
                return $this;
            else
                return $this->builder;
        }


    }

    /**
     * @Name __callStatic
     * @Created by yuxuewen.
     * @Description 动态处理静态方法，后期绑定，实例化所需的子model
     * @param $method  调用的静态方法
     * @param $parameters 调用该静态方法的参数
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}