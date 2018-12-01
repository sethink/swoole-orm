# swoole-orm
基于swoole的mysql协程，简单封装

# 引入
```
>composer require sethink/swoole-orm
```

# 基本使用

## 初始化

在onWorkerStart事件回调中初始化
```php
include_once "./vendor/autoload.php";

$config = [
    //服务器地址
    'host'      => '127.0.0.1',
    //端口
    'port'      => 3306,
    //用户名
    'user'      => 'root',
    //密码
    'password'  => 'root',
    //数据库编码，默认为utf8
    'charset'   => 'utf8',
    //数据库名
    'database'  => 'test',
    //空闲时，队列中保存的最大链接，默认为5
    'poolMin'   => '5',
    //清除队列空闲链接的定时器，默认60s,单位为ms
    'clearTime' => '60000'
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


## 添加

### 添加单条数据

```php
$data = [
    'username'=>'sethink2',
    'password'=>'sethink2',
    'info'=>'ceshi2'
];

Db::init($this->server)
    ->name('user_info')
    ->insert($data);
```    

### 批量添加

```php
$data = [
    [
        'username'=>'sethink3',
        'password'=>'sethink3',
        'info'=>'ceshi3'
    ],
    [
        'username'=>'sethink4',
        'password'=>'password4',
        'info'=>'ceshi4'
    ]
];

Db::init($this->server)
    ->name('user_info')
    ->insertAll($data);
```

## 更新数据

```php
Db::init($this->server)
    ->name('user_info')
    ->where(['username'=>'sethink4'])
    ->update(['password'=>'sethink4-4']);
```

## 删除数据

```php
Db::init($this->server)
    ->name('user_info')
    ->where(['username'=>'sethink4'])
    ->delete();
```
