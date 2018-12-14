# swoole-orm
基于swoole的mysql协程连接池，简单封装。
实现多个协程间共用同一个协程客户端

# 引入
```
>composer require sethink/swoole-orm
```

# 入门例子
```php
<?php
namespace Demo;

include_once "./vendor/autoload.php";

use sethink\swooleOrm\Db;
use sethink\swooleOrm\MysqlPool;
use swoole;

class Demo
{
    protected $server;

    protected $MysqlPool;

    public function __construct($MysqlPool)
    {
        $this->MysqlPool = $MysqlPool;

        $this->server = new Swoole\Http\Server("0.0.0.0", 9501);
        $this->server->set(array(
            'worker_num'    => 4,
            'max_request'   => 50000,
            'reload_async'  => true,
            'max_wait_time' => 30,
        ));

        $this->server->on('Start', function ($server) {});
        $this->server->on('ManagerStart', function ($server) {});
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->server->on('WorkerStop', function ($server, $worker_id) {});
        $this->server->on('open', function ($server, $request) {});
        $this->server->on('Request', array($this, 'onRequest'));
        $this->server->start();
    }

    public function onWorkerStart($server, $worker_id)
    {
        //在其中的一个woker进程中执行定时器
        if($worker_id == 0){
            $this->MysqlPool->clearTimer($server);
        }
    }

    public function onRequest($request, $response)
    {
        $rs = Db::init($this->MysqlPool)
            ->name('tt')
            ->select();
        var_dump($rs);
    }
}

$config    = [
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'user'      => 'root',
    'password'  => 'root',
    'charset'   => 'utf8',
    'database'  => 'test',
    'poolMin'   => '5',
    'clearTime' => '60000'
];
$MysqlPool = new MysqlPool($config);

new Demo($MysqlPool);
```

# 基本使用

## 查询

### 查询单条
```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username,info')
    ->where(['username'=>'sethink','password'=>'sethink'])
    ->find();
```

### 查询多条
```php
<?php

Db::init($this->MysqlPool)
    ->name('info')
    ->field('id,username,password,info')
    ->select();
```


## 添加

### 添加单条数据

```php
<?php

$data = [
    'username' => 'sethink2',
    'password' => 'sethink2',
    'info'     => 'ceshi2'
];

Db::init($this->MysqlPool)
    ->name('user_info')
    ->insert($data);
```    

### 批量添加

```php
<?php

$data = [
    [
        'username' => 'sethink3',
        'password' => 'sethink3',
        'info'     => 'ceshi3'
    ],
    [
        'username' => 'sethink4',
        'password' => 'password4',
        'info'     => 'ceshi4'
    ]
];

Db::init($this->MysqlPool)
    ->name('user_info')
    ->insertAll($data);
```

## 更新数据

```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->where(['username'=>'sethink4'])
    ->update(['password'=>'sethink4-4']);
```

## 删除数据

```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->where(['username'=>'sethink4'])
    ->delete();
```


## 详解

### init($server)
$server为swoole服务器

### name($tableName)
$tableName为表名   --  字符串

### field($field)
$field为查询的字段名   --  字符串

### order($order)
order by排序  --  数组(一维数组或者二维数组)

例子：
$order为一维数组时
```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->order(['id'=>'desc'])
    ->select();
```

$order为二维数组时
```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->order([['id'=>'desc'],['info'=>'asc']])
    ->select();
```

### group($group)
group by分组  --  字符串

例子：
```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->group('info')
    ->select();

```

###　having($having)
用于配置group从分组中筛选数据   --  字符串

例子：
```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->group('info')
    ->having('count(info) > 5')
    ->select();

```

### distinct($distinct)
数据去重
$distinct为bool值

例子：
```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->distinct(true)
    ->select();
```

### fetchSql()
获取sql语句

例子：
```php
<?php


Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->fetchSql()
    ->select();
```

### where($whereArray)
$whereArray为数组

例子1：
```php
<?php
//1、
$where = [
    'id'=>'1'
];

//2、
$where = [
    'id'=>['>',5]
];

//3、
$where = [
    'username'=>['LIKE','%seth%']
];

//4、
$where = [
    'id'=>['in',['1','5']]
];



Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->where($where)
    ->select();
```

### find()
查询一条数据，返回一维数组

### select()
查询一条或多条数据，返回二维数组

### insert($data)
插入单条数据
$data为一维数组

### insertAll($data)
插入多条数据
$data为二维数组

### update($data)
更新数据
$data为一维数组

### delete()
删除数据

### query($sql)
执行sql语句 --  字符串

例子：
```php
<?php
    
$sql = 'select * from `user_info`';
Db::init($this->MysqlPool)->query($sql);
```
