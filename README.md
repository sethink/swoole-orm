# swoole-orm

# 引入
>composer require sethink/swoole-orm

# 基本使用

## 初始化

在onWorkerStart事件回调中
```php
$config = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'user'     => 'root',
    'password' => 'root',
    'charset'  => 'utf8',
    'database' => 'test',
    'poolMin'  => '5',
    'clearTime'=> '60000'
];
$this->server->MysqlPool = new MysqlPool($server,$config);
```

## 查询

### 查询单条
```php
Db::init($this->server)
    ->name('user_info')
    ->field('id,username,info')
    ->where(['username'=>'sethink','password'=>'sethink'])
    ->find();
```

### 查询多条
```php
Db::init($this->server)
    ->name('info')
    ->field('id,username,password,info')
    ->select();
```
