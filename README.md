# laravel-model-extension
extend laravel base model

####查询字段
- 默认为全字段查询

-  已选方式指定需要查询的字段
```php
    User::select('id', 'name')
```
