# swoole-orm

# 引入
>composer require sethink/swoole-orm

# 基本使用
## 查询
```php
Db::init($this->server)
    ->name('test)
    ->field('id,username,info')
    ->where(['username'=>'sethink','password'=>'sethink'])
    ->find();
```
