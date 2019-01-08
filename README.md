# swoole-orm
```
基于swoole的mysql协程连接池，简单封装。  
实现多个协程间共用同一个协程客户端
```

# 版本
## v0.0.1
```
1、初完成

```
## v0.0.2
```
1、bug修复
```
## v0.0.3
```
1、将splqueque修改为channel
2、添加lock()
3、添加日志
4、表前缀

```
## v0.0.4
```
1、添加setDefer -> 设置是否返回结果(默认为true。部分操作，例如insert，update等，如果不需要返回返回结果，则可以设置为false)
2、使用go处理协程

```




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

    public function __construct()
    {
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
        $config = [
            'host'      => '127.0.0.1', //服务器地址
            'port'      => 3306,    //端口
            'user'      => 'root',  //用户名
            'password'  => 'root',  //密码
            'charset'   => 'utf8',  //编码
            'database'  => 'test',  //数据库名
            'prefix'    => 'sethink_',  //表前缀
            'poolMin'   => 5, //空闲时，保存的最大链接，默认为5
            'poolMax'   => 1000,    //地址池最大连接数，默认1000
            'clearTime' => 60000, //清除空闲链接定时器，默认60秒，单位ms
            'clearAll'  => 300000,  //空闲多久清空所有连接，默认5分钟，单位ms
            'setDefer'  => true     //设置是否返回结果,默认为true
        ];
        $this->MysqlPool = new MysqlPool($config);
        unset($config);
        
        //执行定时器
        $this->MysqlPool->clearTimer($server);
    }

    public function onRequest($request, $response)
    {
        $rs = Db::init($this->MysqlPool)
            ->name('tt')
            ->select();
        var_dump($rs);
    }
}

new Demo();
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
```
$server为swoole服务器
```

### name($tableName)
```
$tableName为表名   --  字符串
```

### field($field)
```
$field为查询的字段名   --  字符串
```

### order($order)
```
order by排序  --  数组(一维数组或者二维数组)
```

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
```
group by分组  --  字符串
```

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
```
用于配置group从分组中筛选数据   --  字符串
```

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
```
数据去重
$distinct为bool值
```

例子：
```php
<?php

Db::init($this->MysqlPool)
    ->name('user_info')
    ->field('id,username')
    ->distinct(true)
    ->select();
```

### lock($state)
```
加锁
```

例子：
```php
<?php

//1、传入bool值
Db::init($this->MysqlPool)
    ->name('user_info')
    ->where(['id'=>1])
    ->lock(true)
    ->find();
//会自动在sql语句加上FOR UPDATE

//2、传入字符串
Db::init($this->MysqlPool)
    ->name('user_info')
    ->where(['id'=>1])
    ->lock('lock in share mode')
    ->find();
//特殊锁要求
```


### fetchSql()
```
获取sql语句
```

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
```
$whereArray为数组
```

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
```
查询一条数据，返回一维数组
```

### select()
```
查询一条或多条数据，返回二维数组
```

### insert($data)
```
插入单条数据
$data为一维数组
```

### insertAll($data)
```
插入多条数据
$data为二维数组
```

### update($data)
```
更新数据
$data为一维数组
```

### delete()
```
删除数据
```

### query($sql)
```
执行sql语句 --  字符串
```

例子：
```php
<?php
    
$sql = 'select * from `user_info`';
Db::init($this->MysqlPool)->query($sql);
```

### log($logArray)
```
开启日志功能
$logArray = [
    '类型',
    '信息'
];

$logArray为一维数组，长度为2
$logArray[0]是日志类型
$logArray[1]是日志信息
```

例子：
```php
<?php
$Db::init($this->MysqlPool)
    ->name('user_info')
    ->where(['username'=>'sethink'])
    ->log(['查询用户信息','用户名sethink'])
    ->find();
```

### setDefer($bool)
```
部分操作，例如insert，update等，如果不需要返回结果，则可以设置为false。

相对于$bool为true，sql执行后，由于主进程和协程间不需要再通信，可以立即往下执行程序

也可以全局设置
$config = [
    'host'      => '127.0.0.1', //服务器地址
    'port'      => 3306,    //端口
    'user'      => 'root',  //用户名
    'password'  => 'root',  //密码
    'charset'   => 'utf8',  //编码
    'database'  => 'test',  //数据库名
    'prefix'    => 'sethink_',  //表前缀
    'poolMin'   => 5, //空闲时，保存的最大链接，默认为5
    'setDefer'  => true     //设置是否返回结果,默认为true
];
$this->MysqlPool = new MysqlPool($config);
```

```php
<?php
    //此操作不会返回结果
    Db::init($this->MysqlPool)
        ->name('user_info')
        ->setDefer(false)
        ->insert(['username'=>'sethink_5']);
```