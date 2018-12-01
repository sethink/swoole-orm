# swoole-orm

# 引入
>composer require sethink/swoole-orm

# 基本使用
## 查询
###查询单条
```php
Db::init($this->server)
    ->name('test')
    ->field('id,username,info')
    ->where(['username'=>'sethink','password'=>'sethink'])
    ->find();
```
###查询多条
```
Db::init($this->server)
    ->name('test')
    ->field('id,username,info')
    ->where(['username'=>'sethink','password'=>'sethink'])
    ->select();
```
