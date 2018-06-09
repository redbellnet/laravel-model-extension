# laravel-model-extension
extend laravel base model

####查询字段
- 默认为全字段查询

-  已选方式指定需要查询的字段
```php
    User::select('id', 'name')
```

####查询条件
- 使用方式：
> * 只有一个条件  [ 条件 ]
> * 多个条件     [ [ 条件1 ], [ 条件2 ] ]
               
- 支持的条件方式：                  

> * [field, value]
```php 
        demo                [ 'id', 1 ]
        对应laravel语句      where('id', 1)
```

> * [field , condition, value]
```php 
        demo                [ 'id', '!=', 1 ]
        对应的laravel语句    where('id', '!=', 1)
```

> * [field => value]
```php
        demo                [ 'id' => 1]
        对应laravel语句     where('id', 1)
```

> * [field => [condition, value] ]
```php
        demo                [ id => ['!=', 1] ]
        对应laravel语句     where('id', '!=', 1)
```

> * [condition => [field, args...] ]  此处args为laravel可支持的参数个数
```php
        demo                [ 'whereIn' => ['id', [1,2,3]] ]
        
        demo2               [ 'whereIn' =>
                               [
                                   ['id', [1,2,3]],
                                   ['pid',[1,2,3]]
                               ]
                            ]
        对应laravel语句     whereIn('id', [1, 2, 3])
        
        demo3               [ 'whereColumn' => ['updated_at', '>', 'created_at'] ]
        对应laravel语句     whereColumn('updated_at', '>', 'created_at')
```

                                                     
                       
                       
                      
                       
                       
